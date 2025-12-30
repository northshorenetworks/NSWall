packer {
  required_plugins {
    googlecompute = {
      version = ">= 1.1.0"
      source  = "github.com/hashicorp/googlecompute"
    }
  }
}

variable "version" {
  type    = string
  default = "2.0.0"
}

variable "gcp_project_id" {
  type    = string
  default = env("GCP_PROJECT_ID")
}

variable "gcp_zone" {
  type    = string
  default = "us-central1-a"
}

variable "gcp_machine_type" {
  type    = string
  default = "e2-small"
}

locals {
  image_name   = "nswall-${replace(var.version, ".", "-")}-{{timestamp}}"
  image_family = "nswall"
}

# Note: GCP doesn't have official OpenBSD images
# You'd need to first create a base OpenBSD image from ISO
source "googlecompute" "nswall" {
  project_id   = var.gcp_project_id
  zone         = var.gcp_zone
  machine_type = var.gcp_machine_type

  # Use a custom OpenBSD base image
  source_image_family  = "openbsd-75"
  source_image_project_id = var.gcp_project_id

  ssh_username = "root"

  image_name        = local.image_name
  image_family      = local.image_family
  image_description = "NSWall Network Appliance v${var.version}"

  image_labels = {
    version     = replace(var.version, ".", "-")
    application = "nswall"
    os          = "openbsd"
  }

  disk_size = 10
  disk_type = "pd-ssd"
}

build {
  sources = ["source.googlecompute.nswall"]

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

  # GCP-specific setup
  provisioner "shell" {
    inline = [
      "echo 'Configuring for GCP...'",
      # GCP uses DHCP
      "echo 'dhcp' > /etc/hostname.vio0",
      # Metadata service
      "echo 'pass out quick on egress proto tcp to 169.254.169.254 port 80' >> /etc/pf.conf"
    ]
  }

  provisioner "shell" {
    scripts = ["scripts/cleanup.sh"]
  }

  post-processor "manifest" {
    output = "manifest-gcp.json"
  }
}
