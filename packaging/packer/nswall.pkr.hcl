packer {
  required_plugins {
    qemu = {
      version = ">= 1.0.0"
      source  = "github.com/hashicorp/qemu"
    }
    vmware = {
      version = ">= 1.0.0"
      source  = "github.com/hashicorp/vmware"
    }
  }
}

variable "version" {
  type    = string
  default = "2.0.0"
}

variable "openbsd_version" {
  type    = string
  default = "7.5"
}

variable "openbsd_iso" {
  type    = string
  default = "install75.iso"
}

variable "openbsd_iso_checksum" {
  type    = string
  default = "sha256:XXXX"
}

variable "ssh_password" {
  type    = string
  default = "nswall"
}

variable "disk_size" {
  type    = string
  default = "8G"
}

variable "memory" {
  type    = number
  default = 1024
}

variable "cpus" {
  type    = number
  default = 2
}

# Local variables
locals {
  vm_name      = "nswall-${var.version}"
  output_dir   = "output"
  http_dir     = "http"
  boot_command = [
    "A<enter><wait>",
    "A<enter><wait>",
    "http://{{ .HTTPIP }}:{{ .HTTPPort }}/install.conf<enter><wait60>",
    "<wait10>",
    "reboot<enter>"
  ]
}

# QEMU builder for KVM/Proxmox (QCOW2)
source "qemu" "nswall" {
  vm_name          = "${local.vm_name}"
  iso_url          = "https://cdn.openbsd.org/pub/OpenBSD/${var.openbsd_version}/amd64/${var.openbsd_iso}"
  iso_checksum     = var.openbsd_iso_checksum
  output_directory = "${local.output_dir}/qemu"

  shutdown_command = "shutdown -p now"
  disk_size        = var.disk_size
  format           = "qcow2"
  accelerator      = "kvm"

  headless         = true
  http_directory   = local.http_dir

  ssh_username     = "root"
  ssh_password     = var.ssh_password
  ssh_timeout      = "20m"

  memory           = var.memory
  cpus             = var.cpus

  boot_wait        = "30s"
  boot_command     = local.boot_command

  qemuargs = [
    ["-cpu", "host"],
    ["-smp", "${var.cpus}"],
    ["-m", "${var.memory}"]
  ]
}

# VMware builder (VMDK/OVA)
source "vmware-iso" "nswall" {
  vm_name          = "${local.vm_name}"
  iso_url          = "https://cdn.openbsd.org/pub/OpenBSD/${var.openbsd_version}/amd64/${var.openbsd_iso}"
  iso_checksum     = var.openbsd_iso_checksum
  output_directory = "${local.output_dir}/vmware"

  shutdown_command = "shutdown -p now"
  disk_size        = 8192
  disk_type_id     = "0"

  headless         = true
  http_directory   = local.http_dir

  ssh_username     = "root"
  ssh_password     = var.ssh_password
  ssh_timeout      = "20m"

  memory           = var.memory
  cpus             = var.cpus

  boot_wait        = "30s"
  boot_command     = local.boot_command

  guest_os_type    = "other-64"
  version          = "19"

  vmx_data = {
    "ethernet0.virtualDev" = "vmxnet3"
  }
}

# Build definition
build {
  sources = [
    "source.qemu.nswall",
    "source.vmware-iso.nswall"
  ]

  # Wait for cloud-init or autoinstall to finish
  provisioner "shell" {
    inline = ["echo 'Waiting for system to stabilize...' && sleep 10"]
  }

  # Install NSWall packages
  provisioner "shell" {
    scripts = [
      "scripts/base-setup.sh",
      "scripts/install-nswall.sh",
      "scripts/harden.sh",
      "scripts/cleanup.sh"
    ]
    environment_vars = [
      "NSWALL_VERSION=${var.version}"
    ]
  }

  # Post-processors for different formats
  post-processor "shell-local" {
    only   = ["qemu.nswall"]
    inline = [
      "echo 'Converting to compressed QCOW2...'",
      "qemu-img convert -c -O qcow2 ${local.output_dir}/qemu/${local.vm_name} ${local.output_dir}/${local.vm_name}.qcow2"
    ]
  }

  post-processor "shell-local" {
    only   = ["vmware-iso.nswall"]
    inline = [
      "echo 'Creating OVA...'",
      "cd ${local.output_dir}/vmware && ovftool ${local.vm_name}.vmx ../${local.vm_name}.ova"
    ]
  }
}
