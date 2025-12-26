package nswall

import (
	"context"
	"fmt"
	"strings"

	"github.com/hashicorp/terraform-plugin-sdk/v2/diag"
	"github.com/hashicorp/terraform-plugin-sdk/v2/helper/schema"
)

func resourcePFTableEntry() *schema.Resource {
	return &schema.Resource{
		Description:   "Manages an entry in a PF table",
		CreateContext: resourcePFTableEntryCreate,
		ReadContext:   resourcePFTableEntryRead,
		DeleteContext: resourcePFTableEntryDelete,
		Schema: map[string]*schema.Schema{
			"table": {
				Type:        schema.TypeString,
				Required:    true,
				ForceNew:    true,
				Description: "PF table name",
			},
			"address": {
				Type:        schema.TypeString,
				Required:    true,
				ForceNew:    true,
				Description: "IP address or network",
			},
		},
		Importer: &schema.ResourceImporter{
			StateContext: func(ctx context.Context, d *schema.ResourceData, m interface{}) ([]*schema.ResourceData, error) {
				// ID format: table/address
				parts := strings.SplitN(d.Id(), "/", 2)
				if len(parts) != 2 {
					return nil, fmt.Errorf("invalid import ID, expected: table/address")
				}
				d.Set("table", parts[0])
				d.Set("address", parts[1])
				return []*schema.ResourceData{d}, nil
			},
		},
	}
}

func resourcePFTableEntryCreate(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)

	table := d.Get("table").(string)
	address := d.Get("address").(string)

	_, err := apiRequest(client, "POST", fmt.Sprintf("/api/v1/pf/tables/%s", table), map[string]string{
		"address": address,
	})
	if err != nil {
		return diag.FromErr(err)
	}

	d.SetId(fmt.Sprintf("%s/%s", table, address))
	return resourcePFTableEntryRead(ctx, d, m)
}

func resourcePFTableEntryRead(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	table := d.Get("table").(string)
	address := d.Get("address").(string)

	data, err := apiRequest(client, "GET", fmt.Sprintf("/api/v1/pf/tables/%s", table), nil)
	if err != nil {
		d.SetId("")
		return diags
	}

	tableData, ok := data.(map[string]interface{})
	if !ok {
		d.SetId("")
		return diags
	}

	addresses, ok := tableData["addresses"].([]interface{})
	if !ok {
		d.SetId("")
		return diags
	}

	found := false
	for _, addr := range addresses {
		if addr.(string) == address {
			found = true
			break
		}
	}

	if !found {
		d.SetId("")
	}

	return diags
}

func resourcePFTableEntryDelete(ctx context.Context, d *schema.ResourceData, m interface{}) diag.Diagnostics {
	client := m.(*Client)
	var diags diag.Diagnostics

	table := d.Get("table").(string)
	address := d.Get("address").(string)

	_, err := apiRequest(client, "DELETE", fmt.Sprintf("/api/v1/pf/tables/%s/%s", table, address), nil)
	if err != nil {
		return diag.FromErr(err)
	}

	d.SetId("")
	return diags
}
