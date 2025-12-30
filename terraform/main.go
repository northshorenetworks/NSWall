// NSWall Terraform Provider
package main

import (
	"github.com/hashicorp/terraform-plugin-sdk/v2/plugin"
	"github.com/northshorenetworks/nswall/terraform/nswall"
)

func main() {
	plugin.Serve(&plugin.ServeOpts{
		ProviderFunc: nswall.Provider,
	})
}
