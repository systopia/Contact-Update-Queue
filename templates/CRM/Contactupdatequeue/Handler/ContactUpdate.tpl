<div class="crm-accordion-wrapper crm-contactupdatequeue-contact">
  <div class="crm-accordion-header active">{ts domain="be.aivl.i3val"}Contact Base Data{/ts}</div>
  <div class="crm-accordion-body">
    <table class="contactupdatequeue-table">
      <thead>
        <tr>
          <th></th>
          <th>{ts domain="be.aivl.i3val"}Original Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Submitted Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Current Value{/ts}</th>
          <th>{ts domain="be.aivl.i3val"}Value to apply{/ts}</th>
          <th class="contactupdatequeue-control">{ts domain="be.aivl.i3val"}Apply?{/ts}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$contactupdatequeue_active_contact_fields item=fieldlabel key=fieldkey}
        <tr>
          {capture assign=input_field}{$fieldkey}_applied{/capture}
          {capture assign=checkbox}{$fieldkey}_apply{/capture}
          <td style="vertical-align: middle;">
            {$fieldlabel}
          </td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_active_contact_checkboxes.$fieldkey}
              <input type='checkbox' disabled="disabled" {if $contactupdatequeue_contact_values.$fieldkey.original}checked="checked"{/if}/>
            {else}
              {$contactupdatequeue_contact_values.$fieldkey.original}
            {/if}
          </td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_active_contact_checkboxes.$fieldkey}
              <input type='checkbox' disabled="disabled" {if $contactupdatequeue_contact_values.$fieldkey.submitted}checked="checked"{/if}/>
            {else}
              {$contactupdatequeue_contact_values.$fieldkey.submitted}
            {/if}
          </td>
          <td style="vertical-align: middle;">
            {if $contactupdatequeue_active_contact_checkboxes.$fieldkey}
              <input type='checkbox' disabled="disabled" {if $contactupdatequeue_contact_values.$fieldkey.current}checked="checked"{/if}/>
            {else}
              {$contactupdatequeue_contact_values.$fieldkey.current}
            {/if}
          </td>
          <td style="vertical-align: middle;">
            {if $fieldkey eq 'birth_date'}
              {* doesn't work: {include file="CRM/common/jcalendar.tpl" elementName=birth_date *}
              {$form.$input_field.html}
            {else}
              {$form.$input_field.html}
            {/if}
          </td>
          <td style="vertical-align: middle;" class="contactupdatequeue-control">
            {$form.$checkbox.html}
          </td>
          <!-- <td style="vertical-align: middle;"><input type="text" name="$fieldkey.applied" value="{$contactupdatequeue_contact_values.$fieldkey.applied}" /></td> -->
          <!-- <td style="vertical-align: middle;"><input type="checkbox" name="$fieldkey.apply" checked /></td> -->
        </tr>
        {/foreach}
      </tbody>
    </table>
  </div>
</div>
