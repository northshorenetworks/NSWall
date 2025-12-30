packer {
  required_plugins {
    digitalocean = {
      version = ">= 1.1.0"
      source  = "github.com/digitalocean/digitalocean"
    }
  }
}

variable "version" {
  type    = string
  default = "2.0.0"
}

variable "do_api_token" {
  type      = string
  sensitive = true
  default   = env("DIGITALOCEAN_TOKEN")
}

variable "do_region" {
  type    = string
  default = "nyc1"
}

variable "do_size" {
  type    = string
  default = "s-1vcpu-1gb"
}

locals {
  snapshot_name   = "nswall-${var.version}-{{timestamp}}"
  snapshot_regions = ["nyc1", "sfo3", "ams3", "sgp1"]
}

source "digitalocean" "nswall" {
  api_token    = var.do_api_token
  image        = "openbsd-7-5-x64"
  region       = var.do_region
  size         = var.do_size
  ssh_username = "root"

  snapshot_name    = local.snapshot_name
  snapshot_regions = local.snapshot_regions

  tags = ["nswall", "firewall", "openbsd"]
}

build {
  sources = ["source.digitalocean.nswall"]

  provisioner "shell" {
    scripts = [
      "scripts/base-setup.sh",
      "scripts/install-nswall.sh",
      "scripts/harden.sh"
    ]
    environment_vars = [
      "NSWALL_VERSION=${var.version}"
    ]
  }

  # DigitalOcean-specific setup
  provisioner "shell" {
    inline = [
      "echo 'Configuring for DigitalOcean...'",
      # Configure for DigitalOcean networking
      "echo 'dhcp' > /etc/hostname.vio0",
      # Metadata service
      "echo 'pass out quick on egress proto tcp to 169.254.169.254 port 80' >> /etc/pf.conf"
    ]
  }

  provisioner "shell" {
    scripts = ["scripts/cleanup.sh"]
  }

  post-processor "manifest" {
    output = "manifest-do.json"
  }
}
