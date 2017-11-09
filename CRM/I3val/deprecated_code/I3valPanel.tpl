{*-------------------------------------------------------+
| Ilja's Input Validation Extension                      |
| Amnesty International Vlaanderen                       |
| Copyright (C) 2017 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*}

<table class="i3val">
  <thead style="font-weight: bold;">
    <tr>
      <td>{ts}Field{/ts}</td>
      <td>{ts}Original Value{/ts}</td>
      <td>{ts}Submitted Value{/ts}</td>
      {if $i3val_edit}
      <td>{ts}Value to be Applied{/ts}</td>
      <td>{ts}Apply{/ts}</td>
      {else}
      <td>{ts}Applied Value{/ts}</td>
      {/if}
    </tr>
  </thead>
  <tbody>
    {foreach from=$i3val_values item=value}
    <tr name="{$value.field_name}">
      <td>{$value.title}</td>
      <td>{$value.original}</td>
      <td>{$value.submitted}</td>
      {if $i3val_edit}
      <td><input type="text" value="{$value.submitted}" name="{$value.field_name}" class="i3val-value"/></td>
      <td><input type="checkbox" checked="checked" class="i3val-apply" name="{$value.field_name}"/></td>
      {else}
      <td>{$value.applied}</td>
      {/if}
    </tr>
    {/foreach}

    {if $i3val_edit}
    <tr>
      <td colspan="5">
        <a onclick="i3val_submit(this)" style="margin-right:5px; padding:2px; float:right;" class="edit button" title="{ts}Apply Now{/ts}&nbsp;">
          <span><div class="icon ui-icon-check"></div>{ts}Apply Now{/ts}&nbsp;</span>
        </a>
      </td>
    </tr>
    {/if}
  </tbody>
</table>

{literal}
<script type="text/javascript">

/**
 * submit function, triggered by the "Apply Now" button
 */
function i3val_submit(button) {
  // compile change log
  var table = cj(button).closest("table.i3val");
  var update = {};
  var update_found = false;
  table.find("input.i3val-apply").each(function() {
    var checkbox = cj(this);
    if (checkbox.prop('checked')) {
      var field_name = checkbox.attr('name');
      var value_field = cj("input.i3val-value[name=" + field_name + "]");
      update[field_name] = value_field.val();
      update_found = true;
    }
  });

  if (!update_found) {
    CRM.alert("NOTHING TO DO!", "error");
    return;
  }
  update['activity_id'] = "{/literal}{$i3val_activity.id}{literal}";

  // finally: submit data
  CRM.api('ManualUpdate', 'apply', update, {
    success: function(data) {
      // yay, it worked -> show the good news to the user
      var message = "<p>The following changes were applied:<ul>";
      for (var i = 0; i < data.values.length; i++) {
        message += "<li>" + data.values[i] + "</li>";
      }
      message += "</ul></p>";
      CRM.alert(message, "Changes applied!", "success");

      // reload page
      window.location.reload(true);
    },
    error: function(data) {
      // well, that didn't work -> show the errors to the user
      var message = "<p>The changes could not be applied. The following problems were identified:<ul>";
      for (var i = 0; i < data.error_list.length; i++) {
        message += "<li>" + data.error_list[i] + "</li>";
      }
      message += "</ul></p>";
      CRM.alert(message, "Validation Error", "error");
    }
  });
}
</script>
{/literal}