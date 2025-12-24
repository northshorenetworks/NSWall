#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright: (c) 2024, NSWall Team
# BSD License

DOCUMENTATION = r'''
---
module: nswall_service
short_description: Manage NSWall services
description:
  - Start, stop, restart, reload, enable, or disable services on NSWall.
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
  name:
    description: Service name
    required: true
    type: str
  state:
    description: Desired service state
    type: str
    choices: ['started', 'stopped', 'restarted', 'reloaded']
  enabled:
    description: Whether service is enabled at boot
    type: bool
author:
  - NSWall Team
'''

EXAMPLES = r'''
- name: Start OSPF daemon
  nswall.network.nswall_service:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    name: ospfd
    state: started

- name: Restart BGP daemon
  nswall.network.nswall_service:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    name: bgpd
    state: restarted

- name: Enable and start SSH
  nswall.network.nswall_service:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    name: sshd
    state: started
    enabled: true
'''

RETURN = r'''
service:
  description: Service status after change
  returned: always
  type: dict
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


def get_service_status(module, name):
    """Get current service status"""
    data, error = api_request(module, '/api/v1/services/{}'.format(name))
    if error:
        return None
    return data


def main():
    module = AnsibleModule(
        argument_spec=dict(
            host=dict(type='str', required=True),
            port=dict(type='int', default=8080),
            api_key=dict(type='str', required=True, no_log=True),
            validate_certs=dict(type='bool', default=True),
            name=dict(type='str', required=True),
            state=dict(type='str', choices=['started', 'stopped', 'restarted', 'reloaded']),
            enabled=dict(type='bool'),
        ),
        supports_check_mode=True
    )

    name = module.params['name']
    state = module.params['state']
    enabled = module.params['enabled']

    changed = False
    result = {'name': name}

    # Get current status
    current = get_service_status(module, name)
    if not current:
        module.fail_json(msg="Service '{}' not found".format(name))

    result['before'] = current.copy()

    # Handle enabled/disabled
    if enabled is not None:
        if enabled and not current.get('enabled'):
            if not module.check_mode:
                _, error = api_request(
                    module,
                    '/api/v1/services/{}'.format(name),
                    method='POST',
                    data={'action': 'enable'}
                )
                if error:
                    module.fail_json(msg="Failed to enable service: {}".format(error))
            changed = True
        elif not enabled and current.get('enabled'):
            if not module.check_mode:
                _, error = api_request(
                    module,
                    '/api/v1/services/{}'.format(name),
                    method='POST',
                    data={'action': 'disable'}
                )
                if error:
                    module.fail_json(msg="Failed to disable service: {}".format(error))
            changed = True

    # Handle state changes
    if state:
        action = None
        current_status = current.get('status', '')

        if state == 'started' and current_status != 'running':
            action = 'start'
        elif state == 'stopped' and current_status == 'running':
            action = 'stop'
        elif state == 'restarted':
            action = 'restart'
        elif state == 'reloaded':
            action = 'reload'

        if action:
            if not module.check_mode:
                _, error = api_request(
                    module,
                    '/api/v1/services/{}'.format(name),
                    method='POST',
                    data={'action': action}
                )
                if error:
                    module.fail_json(msg="Failed to {} service: {}".format(action, error))
            changed = True

    # Get final status
    if not module.check_mode:
        result['service'] = get_service_status(module, name)
    else:
        result['service'] = current

    module.exit_json(changed=changed, **result)


if __name__ == '__main__':
    main()
