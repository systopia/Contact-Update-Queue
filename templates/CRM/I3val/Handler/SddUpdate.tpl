<div class="crm-accordion-wrapper crm-i3val-sdd">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}SEPA Direct Debit Update{/ts}</div>
  <div class="crm-accordion-body">
    <div class="crm-section">
      <div class="content">
        <strong>
        {if $i3val_sdd_is_cancel}
          {ts 1=$i3val_sdd_mandate.reference 2=$i3val_sdd_mandate.url domain="be.aivl.i3val"}This is a <i>cancellation</i> of mandate <a href="%2"><code>%1</code></a>.{/ts}
        {else}
          {ts 1=$i3val_sdd_mandate.reference 2=$i3val_sdd_mandate.url domain="be.aivl.i3val"}This is an amendmend for mandate <a href="%2"><code>%1</code></a>.{/ts}
        {/if}
        </strong>
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th></th>
          {if not $i3val_sdd_hide_original}
          <th>{ts domain="be.aivl.i3val"}Original Value{/ts}</th>
          {/if}
          <th>{ts domain="be.aivl.i3val"}Submitted Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Current Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Value to apply{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$i3val_active_sdd_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          {if not $i3val_sdd_hide_original}
          <td style="vertical-align: middle;" sdd_field="{$fieldkey}_original">{$i3val_sdd_values.$fieldkey.original}</td>
          {/if}
          <td style="vertical-align: middle;" sdd_field="{$fieldkey}_submitted">{$i3val_sdd_values.$fieldkey.submitted}</td>
          <td style="vertical-align: middle;" sdd_field="{$fieldkey}_current">{$i3val_sdd_values.$fieldkey.current}</td>
          <td style="vertical-align: middle;" class="i3val-control" sdd_field="{$fieldkey}_applied">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <div class="crm-section" class="i3val-control">
      <div class="label">{$form.i3val_sdd_updates_action.label}</div>
      <div class="content">{$form.i3val_sdd_updates_action.html}</div>
      <div class="clear"></div>
    </div>

  </div>
</div>



<script type="text/javascript">
var i3val_sdd_error_fields = {$i3val_sdd_errors};
{literal}

// apply errors
for (fieldname in i3val_sdd_error_fields) {
  cj("[sdd_field=" + fieldname + "]")
    .addClass("i3val-warning")
    .attr('title', i3val_sdd_error_fields[fieldname]);
}

// add IBAN validation
function i3val_sdd_iban_validation() {
  if (cj("input[name=sdd_iban_applied]").length) {
    var iban = cj("input[name=sdd_iban_applied]").val();
    CRM.api3('I3val', 'service', {
      "handler": "SddUpdate",
      "service": "checkIBAN",
      "iban": iban
    }).done(function(result) {
      if ('iban' in result) {
        // set IBAN
        cj("input[name=sdd_iban_applied]").val(result.iban);

        // set BIC
        if ('bic' in result) {
          cj("input[name=sdd_bic_applied]").val(result.bic);
        } else {
          cj("input[name=sdd_bic_applied]").val();
        }

        // set ERror
        if ('error' in result) {
          cj("input[name=sdd_iban_applied]").parent()
            .addClass("i3val-warning")
            .attr('title', result.error);
        } else {
          cj("input[name=sdd_iban_applied]").parent()
            .removeClass("i3val-warning")
            .attr('title', '');
        }
      }
    });
  }
}
cj(document).ready(function() {
  i3val_sdd_iban_validation();
  cj("input[name=sdd_iban_applied]").change(i3val_sdd_iban_validation);
})

{/literal}
</script>