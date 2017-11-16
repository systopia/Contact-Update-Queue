<div class="crm-accordion-wrapper crm-i3val-address">
  <div class="crm-accordion-header active">{ts}Phone Data{/ts}</div>
  <div class="crm-accordion-body">
    <table>
      <thead>
        <tr>
          <th></th>
          <th>{ts}Original Value{/ts}</th>
          <th>{ts}Submitted Value{/ts}</th>
          <th>{ts}Current Value{/ts}</th>
          <th>{ts}Value to apply{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$i3val_active_phone_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          <td style="vertical-align: middle;">{$i3val_phone_values.$fieldkey.original}</td>
          <td style="vertical-align: middle;">{$i3val_phone_values.$fieldkey.submitted}</td>
          <td style="vertical-align: middle;">{$i3val_phone_values.$fieldkey.current}</td>
          <td style="vertical-align: middle;" class="i3val-control">{$form.$input_field.html}</td>
        </tr>
        {/foreach}
      </tbody>
    </table>

    <hr/>

    {$form.i3val_phone_updates_phone_id.html}

    <div class="crm-section" class="i3val-control">
      <div class="label">{$form.i3val_phone_updates_action.label}</div>
      <div class="content">{$form.i3val_phone_updates_action.html}</div>
      <div class="clear"></div>
    </div>

  </div>
</div>
