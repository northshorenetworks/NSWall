packer {
  required_plugins {
    amazon = {
      version = ">= 1.2.0"
      source  = "github.com/hashicorp/amazon"
    }
  }
}

variable "version" {
  type    = string
  default = "2.0.0"
}

variable "aws_region" {
  type    = string
  default = "us-east-1"
}

variable "aws_instance_type" {
  type    = string
  default = "t3.small"
}

# We use a base OpenBSD AMI and customize it
variable "source_ami_filter" {
  type = object({
    name   = string
    owner  = string
  })
  default = {
    name  = "OpenBSD-7.5-*-amd64-*"
    owner = "self"  # Or community AMI owner ID
  }
}

locals {
  ami_name    = "nswall-${var.version}-{{timestamp}}"
  ami_regions = ["us-east-1", "us-west-2", "eu-west-1"]
}

source "amazon-ebs" "nswall" {
  ami_name        = local.ami_name
  ami_description = "NSWall Network Appliance v${var.version}"
  instance_type   = var.aws_instance_type
  region          = var.aws_region

  source_ami_filter {
    filters = {
      name                = var.source_ami_filter.name
      virtualization-type = "hvm"
      root-device-type    = "ebs"
    }
    owners      = [var.source_ami_filter.owner]
    most_recent = true
  }

  ssh_username = "root"

  # AMI configuration
  ami_regions = local.ami_regions
  ami_groups  = ["all"]  # Make public (remove for private)

  tags = {
    Name        = "NSWall"
    Version     = var.version
    OS          = "OpenBSD 7.5"
    Application = "Network Firewall"
  }

  run_tags = {
    Name = "Packer Builder - NSWall"
  }

  launch_block_device_mappings {
    device_name           = "/dev/sda1"
    volume_size           = 10
    volume_type           = "gp3"
    delete_on_termination = true
  }
}

build {
  sources = ["source.amazon-ebs.nswall"]

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

  # AWS-specific setup
  provisioner "shell" {
    inline = [
      "echo 'Configuring for AWS...'",
      # Enable cloud-init style network config
      "cat >> /etc/hostname.em0 << 'EOF'\ndhcp\nEOF",
      # AWS metadata service access
      "echo 'pass out quick on egress proto tcp to 169.254.169.254 port 80' >> /etc/pf.conf"
    ]
  }

  provisioner "shell" {
    scripts = ["scripts/cleanup.sh"]
  }

  post-processor "manifest" {
    output = "manifest-aws.json"
  }
}
