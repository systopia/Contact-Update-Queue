<div class="crm-accordion-wrapper crm-contactupdatequeue-phone">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}Phone Data{/ts}</div>
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
        {foreach from=$contactupdatequeue_active_phone_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_phone_values.$fieldkey.original}
              <img class="action-icon phone-value-copy" value="{$contactupdatequeue_phone_values.$fieldkey.original}" src="{$contactupdatequeue_icon_copy}" alt="{ts 1=$contactupdatequeuel_phone_values.$fieldkey.original}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$contactupdatequeue_phone_values.$fieldkey.original}Click to copy '%1' into the 'apply' column.{/ts}" />
            {/if}
            {$contactupdatequeue_phone_values.$fieldkey.original}
          </td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_phone_values.$fieldkey.submitted}
              <img class="action-icon phone-value-copy" value="{$contactupdatequeue_phone_values.$fieldkey.submitted}" src="{$contactupdatequeue_icon_copy}" alt="{ts 1=$contactupdatequeue_phone_values.$fieldkey.submitted}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$contactupdatequeue_phone_values.$fieldkey.submitted}Click to copy '%1' into the 'apply' column.{/ts}" />
            {/if}
            {$contactupdatequeue_phone_values.$fieldkey.submitted}
          </td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_phone_values.$fieldkey.current}
              <img class="action-icon phone-value-copy" value="{$contactupdatequeue_phone_values.$fieldkey.current}" src="{$contactupdatequeue_icon_copy}" alt="{ts 1=$contactupdatequeue_phone_values.$fieldkey.current}Click to copy '%1' into the 'apply' column.{/ts}" title="{ts 1=$contactupdatequeue_phone_values.$fieldkey.current}Click to copy '%1' into the 'apply' column.{/ts}" />
            {/if}
            {$contactupdatequeue_phone_values.$fieldkey.current}
          </td>
          <td style="vertical-align: middle;" class="contactupdatequeue-control">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <hr/>

    {$form.contactupdatequeue_phone_updates_phone_id.html}

    <div class="crm-section" class="contactupdatequeue-control">
      <div class="label">{$form.contactupdatequeue_phone_updates_action.label}</div>
      <div class="content">{$form.contactupdatequeue_phone_updates_action.html}</div>
      <div class="clear"></div>
    </div>

  </div>
</div>

{literal}
<script type="text/javascript">
  // add functionality to copy icons
  cj("img.phone-value-copy").click(function() {
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
</script>
{/literal}
