<div class="crm-accordion-wrapper crm-i3val-sdd">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}SEPA Direct Debit Update{/ts}</div>
  <div class="crm-accordion-body">
    <div class="crm-section">
      <div class="content">
        <strong>
        {ts 1=$i3val_sdd_mandate.reference 2=$i3val_sdd_mandate.url domain="be.aivl.i3val"}This is an amendmend for mandate <a href="%2"><code>%1</code></a>.{/ts}
        </strong>
      </div>
    </div>
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
        {foreach from=$i3val_active_sdd_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          <td style="vertical-align: middle;">{$i3val_sdd_values.$fieldkey.original}</td>
          <td style="vertical-align: middle;">{$i3val_sdd_values.$fieldkey.submitted}</td>
          <td style="vertical-align: middle;">{$i3val_sdd_values.$fieldkey.current}</td>
          <td style="vertical-align: middle;" class="i3val-control">{$form.$input_field.html}</td>
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


</script>