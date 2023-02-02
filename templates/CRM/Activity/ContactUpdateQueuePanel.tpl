{*-------------------------------------------------------+
| Contact Update Queue Extension                         |
| Funded by Amnesty International Vlaanderen             |
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

<table class="contactupdatequeue">
  <thead style="font-weight: bold;">
    <tr>
      <td>{ts domain="be.aivl.i3val"}Field{/ts}</td>
      <td>{ts domain="be.aivl.i3val"}Original Value{/ts}</td>
      <td>{ts domain="be.aivl.i3val"}Submitted Value{/ts}</td>
      <td>{ts domain="be.aivl.i3val"}Applied Value{/ts}</td>
    </tr>
  </thead>
  <tbody>
    {foreach from=$contactupdatequeue_values item=value}
    <tr name="{$value.field_name}">
      <td>{$value.title}</td>
      <td>{$value.original}</td>
      <td>{$value.submitted}</td>
      <td>{$value.applied}</td>
    </tr>
    {/foreach}
  </tbody>
</table>
