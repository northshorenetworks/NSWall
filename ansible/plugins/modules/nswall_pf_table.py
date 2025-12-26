#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright: (c) 2024, NSWall Team
# BSD License

DOCUMENTATION = r'''
---
module: nswall_pf_table
short_description: Manage PF table entries
description:
  - Add or remove addresses from PF tables.
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
  table:
    description: PF table name
    required: true
    type: str
  addresses:
    description: IP addresses or networks
    type: list
    elements: str
  state:
    description: Whether addresses should be present or absent
    type: str
    choices: ['present', 'absent', 'flushed']
    default: 'present'
author:
  - NSWall Team
'''

EXAMPLES = r'''
- name: Add IPs to blocklist
  nswall.network.nswall_pf_table:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    table: blocklist
    addresses:
      - 10.1.2.3
      - 10.1.2.4/32
    state: present

- name: Remove IP from allowlist
  nswall.network.nswall_pf_table:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    table: allowlist
    addresses:
      - 192.168.1.100
    state: absent

- name: Flush table
  nswall.network.nswall_pf_table:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    table: bruteforce
    state: flushed
'''

RETURN = r'''
table:
  description: Table info after change
  returned: always
  type: dict
added:
  description: Addresses added
  returned: when state is present
  type: list
removed:
  description: Addresses removed
  returned: when state is absent
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


def get_table(module, name):
    """Get table entries"""
    data, error = api_request(module, '/api/v1/pf/tables/{}'.format(name))
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
            table=dict(type='str', required=True),
            addresses=dict(type='list', elements='str'),
            state=dict(type='str', choices=['present', 'absent', 'flushed'], default='present'),
        ),
        supports_check_mode=True
    )

    table_name = module.params['table']
    addresses = module.params['addresses'] or []
    state = module.params['state']

    changed = False
    added = []
    removed = []

    # Get current table
    current = get_table(module, table_name)
    if current is None:
        module.fail_json(msg="Table '{}' not found".format(table_name))

    current_addresses = set(current.get('addresses', []))

    if state == 'flushed':
        if current_addresses:
            if not module.check_mode:
                _, error = api_request(
                    module,
                    '/api/v1/pf/tables/{}/flush'.format(table_name),
                    method='POST'
                )
                if error:
                    module.fail_json(msg="Failed to flush table: {}".format(error))
            removed = list(current_addresses)
            changed = True

    elif state == 'present':
        for addr in addresses:
            if addr not in current_addresses:
                if not module.check_mode:
                    _, error = api_request(
                        module,
                        '/api/v1/pf/tables/{}'.format(table_name),
                        method='POST',
                        data={'address': addr}
                    )
                    if error:
                        module.fail_json(msg="Failed to add address {}: {}".format(addr, error))
                added.append(addr)
                changed = True

    elif state == 'absent':
        for addr in addresses:
            if addr in current_addresses:
                if not module.check_mode:
                    _, error = api_request(
                        module,
                        '/api/v1/pf/tables/{}/{}'.format(table_name, addr),
                        method='DELETE'
                    )
                    if error:
                        module.fail_json(msg="Failed to remove address {}: {}".format(addr, error))
                removed.append(addr)
                changed = True

    # Get final table state
    result = {
        'table': table_name,
        'added': added,
        'removed': removed,
    }

    if not module.check_mode:
        result['current'] = get_table(module, table_name)

    module.exit_json(changed=changed, **result)


if __name__ == '__main__':
    main()
