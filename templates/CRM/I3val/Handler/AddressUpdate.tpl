<div class="crm-accordion-wrapper crm-i3val-address">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}Address Data{/ts}</div>
  <div class="crm-accordion-body">
    <table>
      <thead>
        <tr>
          <th></th>
          <th class="i3val-address-original">{ts domain="be.aivl.i3val"}Original Value{/ts}</th>
          <th class="i3val-address-submitted">{ts domain="be.aivl.i3val"}Submitted Value{/ts}</th>
          <th class="i3val-address-current">{ts domain="be.aivl.i3val"}Current Value{/ts}</th>
          <th class="i3val-address-applied">{ts domain="be.aivl.i3val"}Value to apply{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$i3val_active_address_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          <td style="vertical-align: middle;">
            {if $i3val_address_values.$fieldkey.original}
              <img class="action-icon address-value-copy" value="{$i3val_address_values.$fieldkey.original}" src="{$i3val_icon_copy}" alt="{ts 1=$i3val_address_values.$fieldkey.original}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$i3val_address_values.$fieldkey.original}Click to copy '%1' into the 'apply' column.{/ts}" />
            {/if}
            {$i3val_address_values.$fieldkey.original}</td>
          <td style="vertical-align: middle;">
            {if $i3val_address_values.$fieldkey.submitted}
              <img class="action-icon address-value-copy" value="{$i3val_address_values.$fieldkey.submitted}" src="{$i3val_icon_copy}" alt="{ts 1=$i3val_address_values.$fieldkey.submitted}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$i3val_address_values.$fieldkey.submitted}Click to copy '%1' into the 'apply' column.{/ts}" />
            {/if}
            {$i3val_address_values.$fieldkey.submitted}
          </td>
          <td class="i3val-address-current i3val-address-current-{$fieldkey}" style="vertical-align: middle;">
            <img style="display: none;" class="action-icon address-value-copy" value="{$i3val_address_values.$fieldkey.current}" src="{$i3val_icon_copy}" alt="{ts 1=$i3val_address_values.$fieldkey.current}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$i3val_address_values.$fieldkey.current}Click to copy '%1' into the 'apply' column.{/ts}" />
            <span>{$i3val_address_values.$fieldkey.current}</span>
          </td>
          <td style="vertical-align: middle;" class="i3val-control">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <!--
    {if $i3val_address_sharing_contact}
    {$form.i3val_address_sharing_contact_id}
    <div class="i3val-suboption">
      <h1><span>{ts domain="be.aivl.i3val"}Share address with{/ts}</span></h1>
      <span class="i3val-suboption-item">
        <a href="{$i3val_address_sharing_contact.link}" title="{$i3val_address_sharing_contact.display_name}"><span class="icon crm-icon {$i3val_address_sharing_contact.contact_type}-icon"></span>{$i3val_address_sharing_contact.display_name}</a>
      </span>
      <span class="i3val-suboption-item">{$form.i3val_address_sharing_addresses.html}</span>
      <span class="i3val-suboption-item">{$form.i3val_address_sharing_location_type.html}</span>
      <span class="i3val-suboption-item i3val-suboption-hint" id="i3val_address_sharing_hint">{ts}This address will be updated as well.{/ts}</span>
    </div>
    {/if}
    -->

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

// add functionality to copy icons
cj("img.address-value-copy").click(function() {
  // find related elements
  var element = cj(this);
  var applied = element.closest("tr").find("[name$=_applied]");

  // copy value
  if (applied.is("input")) {
    // set target value
    applied.val(element.attr('value'));

    // make target flash
    applied.parent().fadeOut(50).fadeIn(50);

  } else if (applied.is("select")) {
    // set target value
    applied.val(element.attr('value'));

    // trigger update
    applied.change();

    // make target flash
    applied.parent().fadeOut(50).fadeIn(50);
  }
});


/**
 * make location type only visible when 'new' is selected
 */
function i3val_address_sharing_update() {
  let value = cj("[name='i3val_address_sharing_addresses']").val();
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