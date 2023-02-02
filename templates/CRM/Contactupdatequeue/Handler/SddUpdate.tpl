<div class="crm-accordion-wrapper crm-contactupdatequeue-sdd">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}SEPA Direct Debit Update{/ts}</div>
  <div class="crm-accordion-body">
    <div class="crm-section">
      <div class="content">
        <strong>
        {if $contactupdatequeue_sdd_is_cancel}
          {ts 1=$contactupdatequeue_sdd_mandate.reference 2=$contactupdatequeue_sdd_mandate.url domain="be.aivl.i3val"}This is a <i>cancellation</i> of mandate <a href="%2"><code>%1</code></a>.{/ts}
        {else}
          {ts 1=$contactupdatequeue_sdd_mandate.reference 2=$contactupdatequeue_sdd_mandate.url domain="be.aivl.i3val"}This is an amendmend for mandate <a href="%2"><code>%1</code></a>.{/ts}
        {/if}
        </strong>
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th></th>
          {if not $contactupdatequeue_sdd_hide_original}
          <th>{ts domain="be.aivl.i3val"}Original Value{/ts}</th>
          {/if}
          <th>{ts domain="be.aivl.i3val"}Submitted Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Current Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Value to apply{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$contactupdatequeue_active_sdd_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          {if not $contactupdatequeue_sdd_hide_original}
          <td style="vertical-align: middle;" sdd_field="{$fieldkey}_original">{$contactupdatequeue_sdd_values.$fieldkey.original}</td>
          {/if}
          <td style="vertical-align: middle;" sdd_field="{$fieldkey}_submitted">{$contactupdatequeue_sdd_values.$fieldkey.submitted}</td>
          <td style="vertical-align: middle;" sdd_field="{$fieldkey}_current">{$contactupdatequeue_sdd_values.$fieldkey.current}</td>
          <td style="vertical-align: middle;" class="contactupdatequeue-control" sdd_field="{$fieldkey}_applied">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <div class="crm-section" class="contactupdatequeue-control">
      <div class="label">{$form.contactupdatequeue_sdd_updates_action.label}</div>
      <div class="content">{$form.contactupdatequeue_sdd_updates_action.html}</div>
      <div class="clear"></div>
    </div>

  </div>
</div>



<script type="text/javascript">
var contactupdatequeue_sdd_error_fields = {$contactupdatequeue_sdd_errors};
{literal}

// apply errors
for (fieldname in contactupdatequeue_sdd_error_fields) {
  cj("[sdd_field=" + fieldname + "]")
    .addClass("contactupdatequeue-warning")
    .attr('title', contactupdatequeue_sdd_error_fields[fieldname]);
}

// add IBAN validation
function contactupdatequeue_sdd_iban_validation() {
  if (cj("input[name=sdd_iban_applied]").length) {
    var iban = cj("input[name=sdd_iban_applied]").val();
    CRM.api3('ContactUpdateQueue', 'service', {
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
        }

        // set ERror
        if ('error' in result) {
          cj("input[name=sdd_iban_applied]").addClass("contactupdatequeue-warning");
          cj("input[name=sdd_iban_applied]").parent()
            .addClass("contactupdatequeue-warning")
            .attr('title', result.error);
        } else {
          cj("input[name=sdd_iban_applied]").removeClass("contactupdatequeue-warning");
          cj("input[name=sdd_iban_applied]").parent()
            .removeClass("contactupdatequeue-warning")
            .attr('title', '');
        }
      }
    });
  }
}
cj(document).ready(function() {
  contactupdatequeue_sdd_iban_validation();
  cj("input[name=sdd_iban_applied]").change(contactupdatequeue_sdd_iban_validation);
})

{/literal}
</script>
