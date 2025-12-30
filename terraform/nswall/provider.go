// Package nswall provides the NSWall Terraform provider
package nswall

import (
	"context"

	"github.com/hashicorp/terraform-plugin-sdk/v2/diag"
	"github.com/hashicorp/terraform-plugin-sdk/v2/helper/schema"
)

// Provider returns the NSWall provider
func Provider() *schema.Provider {
	return &schema.Provider{
		Schema: map[string]*schema.Schema{
			"host": {
				Type:        schema.TypeString,
				Required:    true,
				DefaultFunc: schema.EnvDefaultFunc("NSWALL_HOST", nil),
				Description: "NSWall API host",
			},
			"port": {
				Type:        schema.TypeInt,
				Optional:    true,
				DefaultFunc: schema.EnvDefaultFunc("NSWALL_PORT", 8080),
				Description: "NSWall API port",
			},
			"api_key": {
				Type:        schema.TypeString,
				Required:    true,
				Sensitive:   true,
				DefaultFunc: schema.EnvDefaultFunc("NSWALL_API_KEY", nil),
				Description: "NSWall API key",
			},
			"insecure": {
				Type:        schema.TypeBool,
				Optional:    true,
				Default:     false,
				Description: "Skip TLS verification",
			},
		},
		ResourcesMap: map[string]*schema.Resource{
			"nswall_interface":       resourceInterface(),
			"nswall_route":           resourceRoute(),
			"nswall_pf_table_entry":  resourcePFTableEntry(),
			"nswall_wireguard_peer":  resourceWireGuardPeer(),
			"nswall_carp":            resourceCARP(),
		},
		DataSourcesMap: map[string]*schema.Resource{
			"nswall_system":     dataSourceSystem(),
			"nswall_interfaces": dataSourceInterfaces(),
			"nswall_routes":     dataSourceRoutes(),
			"nswall_pf_tables":  dataSourcePFTables(),
			"nswall_services":   dataSourceServices(),
		},
		ConfigureContextFunc: providerConfigure,
	}
}

// Client holds the API client configuration
type Client struct {
	Host    string
	Port    int
	APIKey  string
	Insecure bool
}

func providerConfigure(ctx context.Context, d *schema.ResourceData) (interface{}, diag.Diagnostics) {
	var diags diag.Diagnostics

	client := &Client{
		Host:     d.Get("host").(string),
		Port:     d.Get("port").(int),
		APIKey:   d.Get("api_key").(string),
		Insecure: d.Get("insecure").(bool),
	}

	return client, diags
}
