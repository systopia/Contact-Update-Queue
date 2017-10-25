<div class="crm-accordion-wrapper crm-i3val-contact">
  <div class="crm-accordion-header active">{ts}Contact Base Data{/ts}</div>
  <div class="crm-accordion-body">
    <table>
      <thead>
        <tr>
          <th></th>
          <th>{ts}Original Value{/ts}</th>
          <th>{ts}Submitted Value{/ts}</th>
          <th>{ts}Current Value{/ts}</th>
          <th>{ts}Future Value{/ts}</th>
          <th>{ts}Apply?{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$i3val_active_contact_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">{$fieldlabel}</td>
          <td style="vertical-align: middle;">{$i3val_contact_values.$fieldkey.original}</td>
          <td style="vertical-align: middle;">{$i3val_contact_values.$fieldkey.submitted}</td>
          <td style="vertical-align: middle;">{$i3val_contact_values.$fieldkey.current}</td>
          <td style="vertical-align: middle;">
            {if $fieldkey eq 'birth_date'}
              {* doesn't work: {include file="CRM/common/jcalendar.tpl" elementName=birth_date *}
              {$form.$input_field.html}
            {else}
              {$form.$input_field.html}
            {/if}
          </td>
          <td style="vertical-align: middle;">{$form.$checkbox.html}</td>
          <!-- <td style="vertical-align: middle;"><input type="text" name="$fieldkey.applied" value="{$i3val_contact_values.$fieldkey.applied}" /></td> -->
          <!-- <td style="vertical-align: middle;"><input type="checkbox" name="$fieldkey.apply" checked /></td> -->
        </tr>
        {/foreach}
      </tbody>
    </table>
  </div>
</div>
