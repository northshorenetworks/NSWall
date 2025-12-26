package nswall

import (
	"context"

	"github.com/hashicorp/terraform-plugin-sdk/v2/diag"
	"github.com/hashicorp/terraform-plugin-sdk/v2/helper/schema"
)

func dataSourceSystem() *schema.Resource {
	return &schema.Resource{
		Description: "Get NSWall system information",
		ReadContext: dataSourceSystemRead,
		Schema: map[string]*schema.Schema{
			"hostname": {
				Type:     schema.TypeString,
				Computed: true,
			},
			"os": {
				Type:     schema.TypeString,
				Computed: true,
			},
			"version": {
				Type:     schema.TypeString,
				Computed: true,
			},
			"arch": {
				Type:     schema.TypeString,
				Computed: true,
			},
			"uptime_seconds": {
				Type:     schema.TypeInt,
				Computed: true,
			},
		},
	}
}

func dataSourceSystemRead(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	data, err := apiRequest(client, "GET", "/api/v1/system", nil)
	if err != nil {
		return diag.FromErr(err)
	}

	system, ok := data.(map[string]interface{})
	if !ok {
		return diag.Errorf("unexpected response format")
	}

	d.SetId(client.Host)
	d.Set("hostname", system["hostname"])
	d.Set("os", system["os"])
	d.Set("version", system["version"])
	d.Set("arch", system["arch"])
	if uptime, ok := system["uptime_seconds"].(float64); ok {
		d.Set("uptime_seconds", int(uptime))
	}

	return diags
}

func dataSourceInterfaces() *schema.Resource {
	return &schema.Resource{
		Description: "Get NSWall network interfaces",
		ReadContext: dataSourceInterfacesRead,
		Schema: map[string]*schema.Schema{
			"interfaces": {
				Type:     schema.TypeList,
				Computed: true,
				Elem: &schema.Resource{
					Schema: map[string]*schema.Schema{
						"name": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"status": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"mac_address": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"mtu": {
							Type:     schema.TypeInt,
							Computed: true,
						},
					},
				},
			},
		},
	}
}

func dataSourceInterfacesRead(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	data, err := apiRequest(client, "GET", "/api/v1/interfaces", nil)
	if err != nil {
		return diag.FromErr(err)
	}

	interfaces, ok := data.([]interface{})
	if !ok {
		return diag.Errorf("unexpected response format")
	}

	var ifaceList []map[string]interface{}
	for _, iface := range interfaces {
		i := iface.(map[string]interface{})
		item := map[string]interface{}{
			"name":        i["name"],
			"status":      i["status"],
			"mac_address": i["mac_address"],
		}
		if mtu, ok := i["mtu"].(float64); ok {
			item["mtu"] = int(mtu)
		}
		ifaceList = append(ifaceList, item)
	}

	d.SetId(client.Host)
	d.Set("interfaces", ifaceList)

	return diags
}

func dataSourceRoutes() *schema.Resource {
	return &schema.Resource{
		Description: "Get NSWall routing table",
		ReadContext: dataSourceRoutesRead,
		Schema: map[string]*schema.Schema{
			"routes": {
				Type:     schema.TypeList,
				Computed: true,
				Elem: &schema.Resource{
					Schema: map[string]*schema.Schema{
						"destination": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"gateway": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"interface": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"flags": {
							Type:     schema.TypeString,
							Computed: true,
						},
					},
				},
			},
		},
	}
}

func dataSourceRoutesRead(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	data, err := apiRequest(client, "GET", "/api/v1/routes", nil)
	if err != nil {
		return diag.FromErr(err)
	}

	routes, ok := data.([]interface{})
	if !ok {
		return diag.Errorf("unexpected response format")
	}

	var routeList []map[string]interface{}
	for _, route := range routes {
		r := route.(map[string]interface{})
		item := map[string]interface{}{
			"destination": r["destination"],
			"gateway":     r["gateway"],
			"interface":   r["interface"],
			"flags":       r["flags"],
		}
		routeList = append(routeList, item)
	}

	d.SetId(client.Host)
	d.Set("routes", routeList)

	return diags
}

func dataSourcePFTables() *schema.Resource {
	return &schema.Resource{
		Description: "Get PF tables",
		ReadContext: dataSourcePFTablesRead,
		Schema: map[string]*schema.Schema{
			"tables": {
				Type:     schema.TypeList,
				Computed: true,
				Elem: &schema.Resource{
					Schema: map[string]*schema.Schema{
						"name": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"count": {
							Type:     schema.TypeInt,
							Computed: true,
						},
					},
				},
			},
		},
	}
}

func dataSourcePFTablesRead(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	data, err := apiRequest(client, "GET", "/api/v1/pf/tables", nil)
	if err != nil {
		return diag.FromErr(err)
	}

	tables, ok := data.([]interface{})
	if !ok {
		return diag.Errorf("unexpected response format")
	}

	var tableList []map[string]interface{}
	for _, table := range tables {
		t := table.(map[string]interface{})
		item := map[string]interface{}{
			"name": t["name"],
		}
		if count, ok := t["count"].(float64); ok {
			item["count"] = int(count)
		}
		tableList = append(tableList, item)
	}

	d.SetId(client.Host)
	d.Set("tables", tableList)

	return diags
}

func dataSourceServices() *schema.Resource {
	return &schema.Resource{
		Description: "Get NSWall services",
		ReadContext: dataSourceServicesRead,
		Schema: map[string]*schema.Schema{
			"services": {
				Type:     schema.TypeList,
				Computed: true,
				Elem: &schema.Resource{
					Schema: map[string]*schema.Schema{
						"name": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"status": {
							Type:     schema.TypeString,
							Computed: true,
						},
						"enabled": {
							Type:     schema.TypeBool,
							Computed: true,
						},
					},
				},
			},
		},
	}
}

func dataSourceServicesRead(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	data, err := apiRequest(client, "GET", "/api/v1/services", nil)
	if err != nil {
		return diag.FromErr(err)
	}

	services, ok := data.([]interface{})
	if !ok {
		return diag.Errorf("unexpected response format")
	}

	var serviceList []map[string]interface{}
	for _, svc := range services {
		s := svc.(map[string]interface{})
		item := map[string]interface{}{
			"name":    s["name"],
			"status":  s["status"],
			"enabled": s["enabled"],
		}
		serviceList = append(serviceList, item)
	}

	d.SetId(client.Host)
	d.Set("services", serviceList)

	return diags
}
