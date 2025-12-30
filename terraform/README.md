# NSWall Terraform Provider

Terraform provider for NSWall network appliance automation.

## Installation

### From Registry (Coming Soon)

```hcl
terraform {
  required_providers {
    nswall = {
      source  = "northshorenetworks/nswall"
      version = "~> 1.0"
    }
  }
}
```

### From Source

```bash
cd terraform
go build -o terraform-provider-nswall
mkdir -p ~/.terraform.d/plugins/northshorenetworks/nswall/1.0.0/linux_amd64
mv terraform-provider-nswall ~/.terraform.d/plugins/northshorenetworks/nswall/1.0.0/linux_amd64/
```

## Configuration

```hcl
provider "nswall" {
  host    = "nswall.example.com"
  port    = 8080
  api_key = var.nswall_api_key
}
```

Or use environment variables:

```bash
export NSWALL_HOST="nswall.example.com"
export NSWALL_PORT="8080"
export NSWALL_API_KEY="your-api-key"
```

## Data Sources

### nswall_system

Get system information.

```hcl
data "nswall_system" "info" {}

output "hostname" {
  value = data.nswall_system.info.hostname
}
```

### nswall_interfaces

Get network interfaces.

```hcl
data "nswall_interfaces" "all" {}

output "interface_count" {
  value = length(data.nswall_interfaces.all.interfaces)
}
```

### nswall_routes

Get routing table.

```hcl
data "nswall_routes" "all" {}
```

### nswall_pf_tables

Get PF tables.

```hcl
data "nswall_pf_tables" "all" {}
```

### nswall_services

Get services status.

```hcl
data "nswall_services" "all" {}
```

## Resources

### nswall_route

Manage static routes.

```hcl
resource "nswall_route" "internal" {
  destination = "10.0.0.0/8"
  gateway     = "192.168.1.254"
}

resource "nswall_route" "vpn" {
  destination = "172.16.0.0/12"
  interface   = "wg0"
}
```

### nswall_pf_table_entry

Manage PF table entries.

```hcl
resource "nswall_pf_table_entry" "blocked_ip" {
  table   = "blocklist"
  address = "10.1.2.3"
}

resource "nswall_pf_table_entry" "blocked_network" {
  table   = "blocklist"
  address = "192.168.100.0/24"
}
```

### nswall_interface

Configure network interfaces.

```hcl
resource "nswall_interface" "lan" {
  name        = "em1"
  description = "LAN interface"
  ipv4        = ["192.168.1.1/24"]
  admin_up    = true
}
```

### nswall_wireguard_peer

Manage WireGuard peers.

```hcl
resource "nswall_wireguard_peer" "remote" {
  interface   = "wg0"
  public_key  = "abc123..."
  endpoint    = "203.0.113.1:51820"
  allowed_ips = ["10.0.0.2/32"]
  persistent_keepalive = 25
}
```

### nswall_carp

Configure CARP for high availability.

```hcl
resource "nswall_carp" "vip" {
  interface  = "carp0"
  vhid       = 1
  virtual_ip = "192.168.1.254/24"
  advbase    = 1
  advskew    = 0
  password   = var.carp_password
}
```

## Example

Complete example with firewall rules and VPN:

```hcl
terraform {
  required_providers {
    nswall = {
      source = "northshorenetworks/nswall"
    }
  }
}

provider "nswall" {
  host    = var.nswall_host
  api_key = var.nswall_api_key
}

# Get current system info
data "nswall_system" "current" {}

# Static routes
resource "nswall_route" "datacenter" {
  destination = "10.100.0.0/16"
  gateway     = "192.168.1.254"
}

# Block malicious IPs
resource "nswall_pf_table_entry" "blocked" {
  for_each = toset(var.blocked_ips)

  table   = "blocklist"
  address = each.value
}

# WireGuard peers
resource "nswall_wireguard_peer" "sites" {
  for_each = var.wireguard_peers

  interface   = "wg0"
  public_key  = each.value.public_key
  endpoint    = each.value.endpoint
  allowed_ips = each.value.allowed_ips
}

variable "nswall_host" {
  type = string
}

variable "nswall_api_key" {
  type      = string
  sensitive = true
}

variable "blocked_ips" {
  type    = list(string)
  default = []
}

variable "wireguard_peers" {
  type = map(object({
    public_key  = string
    endpoint    = string
    allowed_ips = list(string)
  }))
  default = {}
}

output "system_info" {
  value = {
    hostname = data.nswall_system.current.hostname
    version  = data.nswall_system.current.version
  }
}
```

## Import

Resources can be imported using their identifiers:

```bash
# Import a route
terraform import nswall_route.internal 10.0.0.0/8

# Import a PF table entry
terraform import nswall_pf_table_entry.blocked blocklist/10.1.2.3
```

## License

BSD
