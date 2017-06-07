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
      <td>{ts}Value to be Applied{/ts}</td>
      {if $i3val_edit}
      <td>{ts}Apply{/ts}</td>
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
      <td><input type="text" value="{$value.submitted}" name="{$value.field_name}"/></td>
      <td><input type="checkbox" checked="checked" name="{$value.field_name}"/></td>
      {else}
      <td>{$value.applied}</td>
      {/if}
    </tr>
    {/foreach}

    {if $i3val_edit}
    <tr>
      <td colspan="5">
        <a style="margin-right:5px; padding:2px; float:right;" class="edit button" title="{ts}Apply Now{/ts}&nbsp;">
          <span><div class="icon ui-icon-check"></div>{ts}Apply Now{/ts}&nbsp;</span>
        </a>
      </td>
    </tr>
    {/if}
  </tbody>
</table>
