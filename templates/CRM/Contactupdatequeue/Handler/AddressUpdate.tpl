<div class="crm-accordion-wrapper crm-contactupdatequeue-address">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}Address Data{/ts}</div>
  <div class="crm-accordion-body">
    <table>
      <thead>
        <tr>
          <th></th>
          <th class="contactupdatequeue-address-original">{ts domain="be.aivl.i3val"}Original Value{/ts}</th>
          <th class="contactupdatequeue-address-submitted">{ts domain="be.aivl.i3val"}Submitted Value{/ts}</th>
          <th class="contactupdatequeue-address-current">{ts domain="be.aivl.i3val"}Current Value{/ts}</th>
          <th class="contactupdatequeue-address-applied">{ts domain="be.aivl.i3val"}Value to apply{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$contactupdatequeue_active_address_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_address_values.$fieldkey.original}
              <img class="action-icon address-value-copy" value="{$contactupdatequeue_address_values.$fieldkey.original}" src="{$contactupdatequeue_icon_copy}" alt="{ts 1=$contactupdatequeue_address_values.$fieldkey.original}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$contactupdatequeue_address_values.$fieldkey.original}Click to copy '%1' into the 'apply' column.{/ts}" />
            {/if}
            {$contactupdatequeue_address_values.$fieldkey.original}</td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_address_values.$fieldkey.submitted}
              <img class="action-icon address-value-copy" value="{$contactupdatequeue_address_values.$fieldkey.submitted}" src="{$contactupdatequeue_icon_copy}" alt="{ts 1=$contactupdatequeue_address_values.$fieldkey.submitted}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$contactupdatequeue_address_values.$fieldkey.submitted}Click to copy '%1' into the 'apply' column.{/ts}" />
            {/if}
            {$contactupdatequeue_address_values.$fieldkey.submitted}
          </td>
          <td class="contactupdatequeue-address-current contactupdatequeue-address-current-{$fieldkey}" style="vertical-align: middle;">
            <img style="display: none;" class="action-icon address-value-copy" value="{$contactupdatequeue_address_values.$fieldkey.current}" src="{$contactupdatequeue_icon_copy}" alt="{ts 1=$contactupdatequeue_address_values.$fieldkey.current}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$contactupdatequeue_address_values.$fieldkey.current}Click to copy '%1' into the 'apply' column.{/ts}" />
            <span>{$contactupdatequeue_address_values.$fieldkey.current}</span>
          </td>
          <td style="vertical-align: middle;" class="contactupdatequeue-control">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <!-- mitigation for missing address sharing, see https://github.com/systopia/Contact-Update-Queue/issues/42 -->
    {if $contactupdatequeue_address_sharing_mitigation}
      <hr/>
      <div class="contactupdatequeue-note">{ts domain="be.aivl.i3val"}<b>Note:</b>{/ts} {$contactupdatequeue_address_sharing_mitigation} {ts domain="be.aivl.i3val"}You might have to manually adjust the resulting address(es).{/ts}</div>
    {/if}

    <!--
    {if $contactupdatequeue_address_sharing_contact}
    {$form.contactupdatequeue_address_sharing_contact_id}
    <div class="contactupdatequeue-suboption">
      <h1><span>{ts domain="be.aivl.i3val"}Share address with{/ts}</span></h1>
      <span class="contactupdatequeue-suboption-item">
        <a href="{$contactupdatequeue_address_sharing_contact.link}" title="{$contactupdatequeue_address_sharing_contact.display_name}"><span class="icon crm-icon {$contactupdatequeue_address_sharing_contact.contact_type}-icon"></span>{$contactupdatequeue_address_sharing_contact.display_name}</a>
      </span>
      <span class="contactupdatequeue-suboption-item">{$form.contactupdatequeue_address_sharing_addresses.html}</span>
      <span class="contactupdatequeue-suboption-item">{$form.contactupdatequeue_address_sharing_location_type.html}</span>
      <span class="contactupdatequeue-suboption-item contactupdatequeue-suboption-hint" id="contactupdatequeue_address_sharing_hint">{ts}This address will be updated as well.{/ts}</span>
    </div>
    {/if}
    -->

    <hr/>

    <div class="crm-section" class="contactupdatequeue-control">
      <div class="label">{$form.contactupdatequeue_address_updates_action.label}</div>
      <div class="content">{$form.contactupdatequeue_address_updates_action.html}</div>
      <div class="clear"></div>
    </div>

    {$form.contactupdatequeue_email_updates_address_id.html}

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
function contactupdatequeue_address_sharing_update() {
  let value = cj("[name='contactupdatequeue_address_sharing_addresses']").val();
  if (value == 'new') {
    cj("[name='contactupdatequeue_address_sharing_location_type']").show(200);
  } else {
    cj("[name='contactupdatequeue_address_sharing_location_type']").hide();
  }

  if (value == 'new' || value == 'none') {
    cj("#contactupdatequeue_address_sharing_hint").hide();
  } else {
    cj("#contactupdatequeue_address_sharing_hint").show();
  }
}
cj("[name='contactupdatequeue_address_sharing_addresses']").change(contactupdatequeue_address_sharing_update);
contactupdatequeue_address_sharing_update();
</script>
{/literal}
