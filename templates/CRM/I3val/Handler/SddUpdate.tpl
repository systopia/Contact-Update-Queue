<div class="crm-accordion-wrapper crm-i3val-sdd">
  <div class="crm-accordion-header active">{ts}SEPA Direct Debit Update{/ts}</div>
  <div class="crm-accordion-body">
    <div class="crm-section">
      <div class="content">
        {ts 1=$i3val_sdd_mandate.reference 2=$i3val_sdd_mandate.url}This is an amendmend for mandate <a href="%2"><code>%1</code></a>.{/ts}
      </div>
    </div>
    <table>
      <thead>
        <tr>
          <th></th>
          <th>{ts}Original Value{/ts}</th>
          <th>{ts}Submitted Value{/ts}</th>
          <th>{ts}Current Value{/ts}</th>
          <th>{ts}Value to use{/ts}</th>
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
          <td style="vertical-align: middle;">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <div class="crm-section crm-i3val-sdd">
      <div class="content crm-i3val-sdd-newmandate">
        {ts}This will trigger the creation of a new mandate that will seamlessly replace the current one.{/ts}
      </div>
      <div class="content crm-i3val-sdd-amendment">
        {ts}The current mandate will be adjusted{/ts}
      </div>
      <div class="content crm-i3val-sdd-nochange">
        {ts}No changes will be performed.{/ts}
      </div>
    </div>
  </div>
</div>
