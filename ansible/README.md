# NSWall Ansible Collection

Ansible Collection for NSWall network appliance automation.

## Installation

```bash
ansible-galaxy collection install nswall.network
```

Or install from source:

```bash
cd ansible
ansible-galaxy collection build
ansible-galaxy collection install nswall-network-1.0.0.tar.gz
```

## Requirements

- Ansible 2.10+
- NSWall appliance with API enabled

## Modules

### nswall_facts
Gather facts from NSWall appliance.

```yaml
- name: Gather NSWall facts
  nswall.network.nswall_facts:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
  register: facts

- debug:
    var: facts.ansible_facts
```

### nswall_interface
Manage network interfaces.

```yaml
- name: Configure interface
  nswall.network.nswall_interface:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    name: em0
    description: "WAN uplink"
    ipv4:
      - 192.168.1.1/24
    state: present
```

### nswall_route
Manage routing table.

```yaml
- name: Add static route
  nswall.network.nswall_route:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    destination: 10.0.0.0/8
    gateway: 192.168.1.254
    state: present
```

### nswall_pf_table
Manage PF tables.

```yaml
- name: Add IP to blocklist
  nswall.network.nswall_pf_table:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    table: blocklist
    addresses:
      - 10.1.2.3
      - 10.1.2.4
    state: present
```

### nswall_service
Manage services/daemons.

```yaml
- name: Restart OSPF daemon
  nswall.network.nswall_service:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    name: ospfd
    state: restarted
```

### nswall_wireguard_peer
Manage WireGuard peers.

```yaml
- name: Add WireGuard peer
  nswall.network.nswall_wireguard_peer:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    interface: wg0
    public_key: "abc123..."
    endpoint: "203.0.113.1:51820"
    allowed_ips:
      - 10.0.0.2/32
    state: present
```

### nswall_config
Manage configuration.

```yaml
- name: Save running config
  nswall.network.nswall_config:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    action: save

- name: Backup configuration
  nswall.network.nswall_config:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    action: backup
    backup_name: "pre-upgrade"
```

### nswall_command
Execute arbitrary NSH commands.

```yaml
- name: Show running config
  nswall.network.nswall_command:
    host: "{{ nswall_host }}"
    api_key: "{{ nswall_api_key }}"
    commands:
      - show running-config
      - show interface
  register: output
```

## Inventory Plugin

Use the `nswall` inventory plugin to dynamically discover appliances:

```yaml
# nswall.yml
plugin: nswall.network.nswall
host: nswall.example.com
api_key: "{{ lookup('env', 'NSWALL_API_KEY') }}"
```

## Role: nswall_base

Basic NSWall configuration role:

```yaml
- hosts: firewalls
  roles:
    - role: nswall.network.nswall_base
      vars:
        nswall_hostname: fw01
        nswall_interfaces:
          - name: em0
            description: WAN
            ipv4: dhcp
          - name: em1
            description: LAN
            ipv4: 192.168.1.1/24
        nswall_routes:
          - destination: 10.0.0.0/8
            gateway: 192.168.1.254
```

## Environment Variables

- `NSWALL_HOST` - Default API host
- `NSWALL_API_KEY` - Default API key
- `NSWALL_VERIFY_SSL` - Verify SSL certificates (default: true)

## License

BSD
