<div class="crm-accordion-wrapper crm-i3val-address">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}Address Data{/ts}</div>
  <div class="crm-accordion-body">
    <table>
      <thead>
        <tr>
          <th></th>
          <th>{ts domain="be.aivl.i3val"}Original Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Submitted Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Current Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Value to apply{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$i3val_active_address_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          <td style="vertical-align: middle;">{$i3val_address_values.$fieldkey.original}</td>
          <td style="vertical-align: middle;">{$i3val_address_values.$fieldkey.submitted}</td>
          <td style="vertical-align: middle;">{$i3val_address_values.$fieldkey.current}</td>
          <td style="vertical-align: middle;" class="i3val-control">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    {if $i3val_address_sharing_contact}
    {$form.i3val_address_sharing_contact_id}
    <div class="i3val-suboption">
      <h1><span>{ts}Share address with{/ts}</span></h1>
      <span class="i3val-suboption-item">
        <a href="{$i3val_address_sharing_contact.link}" title="{$i3val_address_sharing_contact.display_name}"><span class="icon crm-icon {$i3val_address_sharing_contact.contact_type}-icon"></span>{$i3val_address_sharing_contact.display_name}</a>
      </span>
      <span class="i3val-suboption-item">{$form.i3val_address_sharing_addresses.html}</span>
      <span class="i3val-suboption-item">{$form.i3val_address_sharing_location_type.html}</span>
      <span class="i3val-suboption-item i3val-suboption-hint" id="i3val_address_sharing_hint">{ts}This address will be updated as well.{/ts}</span>
    </div>
    {/if}

    <hr/>

    <div class="crm-section" class="i3val-control">
      <div class="label">{$form.i3val_address_updates_action.label}</div>
      <div class="content">{$form.i3val_address_updates_action.html}</div>
      <div class="clear"></div>
    </div>

    {$form.i3val_email_updates_address_id.html}

  </div>
</div>

{literal}
<script type="text/javascript">

/**
 * make location type only visible when 'new' is selected
 */
function i3val_address_sharing_update() {
  var value = cj("[name='i3val_address_sharing_addresses']").val();
  if (value == 'new') {
    cj("[name='i3val_address_sharing_location_type']").show(200);
  } else {
    cj("[name='i3val_address_sharing_location_type']").hide();
  }

  if (value == 'new' || value == 'none') {
    cj("#i3val_address_sharing_hint").hide();
  } else {
    cj("#i3val_address_sharing_hint").show();
  }
}
cj("[name='i3val_address_sharing_addresses']").change(i3val_address_sharing_update);
i3val_address_sharing_update();
</script>
{/literal}