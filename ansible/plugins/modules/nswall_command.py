#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright: (c) 2024, NSWall Team
# BSD License

DOCUMENTATION = r'''
---
module: nswall_command
short_description: Execute NSH commands
description:
  - Execute arbitrary NSH commands on NSWall.
version_added: "1.0.0"
options:
  host:
    description: NSWall API host
    required: true
    type: str
  port:
    description: NSWall API port
    type: int
    default: 8080
  api_key:
    description: API key for authentication
    required: true
    type: str
    no_log: true
  validate_certs:
    description: Validate SSL certificates
    type: bool
    default: true
  commands:
    description: List of commands to execute
    required: true
    type: list
    elements: str
author:
  - NSWall Team
'''

EXAMPLES = r'''
- name: Show configuration
  nswall.network.nswall_command:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    commands:
      - show running-config
  register: config

- name: Multiple commands
  nswall.network.nswall_command:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    commands:
      - show interface
      - show route
      - show arp
'''

RETURN = r'''
results:
  description: Command outputs
  returned: always
  type: list
  elements: dict
stdout_lines:
  description: All output lines combined
  returned: always
  type: list
'''

import json
from ansible.module_utils.basic import AnsibleModule
from ansible.module_utils.urls import fetch_url


def api_request(module, endpoint, method='GET', data=None):
    """Make API request to NSWall"""
    url = "http://{host}:{port}{endpoint}".format(
        host=module.params['host'],
        port=module.params['port'],
        endpoint=endpoint
    )

    headers = {
        'Content-Type': 'application/json',
        'X-API-Key': module.params['api_key']
    }

    if data:
        data = json.dumps(data)

    response, info = fetch_url(
        module,
        url,
        method=method,
        headers=headers,
        data=data,
        validate_certs=module.params['validate_certs']
    )

    if info['status'] not in [200, 201]:
        return None, info.get('msg', 'Unknown error')

    try:
        body = json.loads(response.read())
        if body.get('success'):
            return body.get('data'), None
        return None, body.get('error', 'Unknown error')
    except Exception as e:
        return None, str(e)


def main():
    module = AnsibleModule(
        argument_spec=dict(
            host=dict(type='str', required=True),
            port=dict(type='int', default=8080),
            api_key=dict(type='str', required=True, no_log=True),
            validate_certs=dict(type='bool', default=True),
            commands=dict(type='list', elements='str', required=True),
        ),
        supports_check_mode=True
    )

    commands = module.params['commands']
    results = []
    stdout_lines = []

    for cmd in commands:
        if module.check_mode:
            results.append({
                'command': cmd,
                'output': '(check mode)',
                'exit_code': 0
            })
            continue

        data, error = api_request(
            module,
            '/api/v1/command',
            method='POST',
            data={'command': cmd}
        )

        if error:
            module.fail_json(msg="Command '{}' failed: {}".format(cmd, error))

        result = {
            'command': cmd,
            'output': data.get('output', ''),
            'exit_code': data.get('exit_code', 0)
        }
        results.append(result)

        # Add to combined stdout
        stdout_lines.extend(data.get('output', '').splitlines())

    module.exit_json(
        changed=False,
        results=results,
        stdout_lines=stdout_lines
    )


if __name__ == '__main__':
    main()
