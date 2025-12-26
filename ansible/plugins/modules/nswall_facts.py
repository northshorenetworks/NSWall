#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright: (c) 2024, NSWall Team
# BSD License

DOCUMENTATION = r'''
---
module: nswall_facts
short_description: Gather facts from NSWall appliance
description:
  - Gathers system, interface, and configuration facts from NSWall.
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
  gather_subset:
    description: Facts to gather
    type: list
    elements: str
    default: ['all']
    choices: ['all', 'system', 'interfaces', 'routes', 'arp', 'pf', 'services', 'config']
author:
  - NSWall Team
'''

EXAMPLES = r'''
- name: Gather all facts
  nswall.network.nswall_facts:
    host: nswall.example.com
    api_key: "{{ api_key }}"

- name: Gather only interface facts
  nswall.network.nswall_facts:
    host: nswall.example.com
    api_key: "{{ api_key }}"
    gather_subset:
      - interfaces
'''

RETURN = r'''
ansible_facts:
  description: Facts gathered from NSWall
  returned: always
  type: dict
  contains:
    nswall_system:
      description: System information
      type: dict
    nswall_interfaces:
      description: Network interfaces
      type: list
    nswall_routes:
      description: Routing table
      type: list
    nswall_arp:
      description: ARP table
      type: list
    nswall_pf:
      description: PF firewall info
      type: dict
    nswall_services:
      description: Service status
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

    if info['status'] != 200:
        return None, info.get('msg', 'Unknown error')

    try:
        body = json.loads(response.read())
        if body.get('success'):
            return body.get('data'), None
        return None, body.get('error', 'Unknown error')
    except Exception as e:
        return None, str(e)


def gather_system_facts(module):
    """Gather system facts"""
    data, error = api_request(module, '/api/v1/system')
    if error:
        return {}
    return data or {}


def gather_interface_facts(module):
    """Gather interface facts"""
    data, error = api_request(module, '/api/v1/interfaces')
    if error:
        return []
    return data or []


def gather_route_facts(module):
    """Gather route facts"""
    data, error = api_request(module, '/api/v1/routes')
    if error:
        return []
    return data or []


def gather_arp_facts(module):
    """Gather ARP facts"""
    data, error = api_request(module, '/api/v1/arp')
    if error:
        return []
    return data or []


def gather_pf_facts(module):
    """Gather PF facts"""
    data, error = api_request(module, '/api/v1/pf')
    if error:
        return {}
    return data or {}


def gather_service_facts(module):
    """Gather service facts"""
    data, error = api_request(module, '/api/v1/services')
    if error:
        return []
    return data or []


def main():
    module = AnsibleModule(
        argument_spec=dict(
            host=dict(type='str', required=True),
            port=dict(type='int', default=8080),
            api_key=dict(type='str', required=True, no_log=True),
            validate_certs=dict(type='bool', default=True),
            gather_subset=dict(
                type='list',
                elements='str',
                default=['all'],
                choices=['all', 'system', 'interfaces', 'routes', 'arp', 'pf', 'services', 'config']
            ),
        ),
        supports_check_mode=True
    )

    subset = module.params['gather_subset']
    gather_all = 'all' in subset

    facts = {}

    if gather_all or 'system' in subset:
        facts['nswall_system'] = gather_system_facts(module)

    if gather_all or 'interfaces' in subset:
        facts['nswall_interfaces'] = gather_interface_facts(module)

    if gather_all or 'routes' in subset:
        facts['nswall_routes'] = gather_route_facts(module)

    if gather_all or 'arp' in subset:
        facts['nswall_arp'] = gather_arp_facts(module)

    if gather_all or 'pf' in subset:
        facts['nswall_pf'] = gather_pf_facts(module)

    if gather_all or 'services' in subset:
        facts['nswall_services'] = gather_service_facts(module)

    module.exit_json(changed=False, ansible_facts=facts)


if __name__ == '__main__':
    main()
