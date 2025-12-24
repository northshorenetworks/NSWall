package nswall

import (
	"context"
	"fmt"

	"github.com/hashicorp/terraform-plugin-sdk/v2/diag"
	"github.com/hashicorp/terraform-plugin-sdk/v2/helper/schema"
)

func resourceRoute() *schema.Resource {
	return &schema.Resource{
		Description:   "Manages a static route on NSWall",
		CreateContext: resourceRouteCreate,
		ReadContext:   resourceRouteRead,
		DeleteContext: resourceRouteDelete,
		Schema: map[string]*schema.Schema{
			"destination": {
				Type:        schema.TypeString,
				Required:    true,
				ForceNew:    true,
				Description: "Destination network (CIDR notation)",
			},
			"gateway": {
				Type:        schema.TypeString,
				Optional:    true,
				ForceNew:    true,
				Description: "Gateway IP address",
			},
			"interface": {
				Type:        schema.TypeString,
				Optional:    true,
				ForceNew:    true,
				Description: "Outgoing interface",
			},
			"priority": {
				Type:        schema.TypeInt,
				Optional:    true,
				ForceNew:    true,
				Description: "Route priority",
			},
			"label": {
				Type:        schema.TypeString,
				Optional:    true,
				ForceNew:    true,
				Description: "Route label",
			},
		},
		Importer: &schema.ResourceImporter{
			StateContext: schema.ImportStatePassthroughContext,
		},
	}
}

func resourceRouteCreate(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)

	route := map[string]interface{}{
		"destination": d.Get("destination").(string),
	}

	if v, ok := d.GetOk("gateway"); ok {
		route["gateway"] = v.(string)
	}
	if v, ok := d.GetOk("interface"); ok {
		route["interface"] = v.(string)
	}
	if v, ok := d.GetOk("priority"); ok {
		route["priority"] = v.(int)
	}
	if v, ok := d.GetOk("label"); ok {
		route["label"] = v.(string)
	}

	_, err := apiRequest(client, "POST", "/api/v1/routes", route)
	if err != nil {
		return diag.FromErr(err)
	}

	d.SetId(d.Get("destination").(string))
	return resourceRouteRead(ctx, d, m)
}

func resourceRouteRead(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
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

	destination := d.Id()
	found := false

	for _, r := range routes {
		route := r.(map[string]interface{})
		if route["destination"] == destination {
			d.Set("destination", route["destination"])
			d.Set("gateway", route["gateway"])
			d.Set("interface", route["interface"])
			found = true
			break
		}
	}

	if !found {
		d.SetId("")
	}

	return diags
}

func resourceRouteDelete(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	destination := d.Id()
	_, err := apiRequest(client, "DELETE", fmt.Sprintf("/api/v1/routes/%s", destination), nil)
	if err != nil {
		return diag.FromErr(err)
	}

	d.SetId("")
	return diags
}
