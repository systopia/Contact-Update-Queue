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
+-------------------------------------------------------*}

{literal}
<style type="text/css">

div.i3val-config {
  border: 2px solid #a1a1a1;
  padding: 0px 10px;
  margin-top: 20px;
  /*background:#dddddd;*/
  /*width:300px;*/
  border-radius:15px;
}
</style>
{/literal}

<h3>{ts}General Options{/ts}</h3>

<div>
  <div class="crm-section">
    <div class="label">{$form.flag_status.label}</div>
    <div class="content">{$form.flag_status.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.strip_chars.label}</div>
    <div class="content">{$form.strip_chars.html}</div>
    <div class="clear"></div>
  </div>
</div>

<br/>
<h3>{ts}Processing Session Options{/ts}</h3>

<div>
  <div class="crm-section">
    <div class="label">{$form.session_ttl.label}</div>
    <div class="content">{$form.session_ttl.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.session_size.label}</div>
    <div class="content">{$form.session_size.html}</div>
    <div class="clear"></div>
  </div>
</div>

<br/>
<h3>{ts}Data Configuration{/ts}</h3>

{foreach from=$configurations item=index}
  {capture assign=actvivity_type}activity_type_id_{$index}{/capture}
  {capture assign=handlers}handler_classes_{$index}{/capture}
  <div class="i3val-config">
    <h2>{ts 1=$index}Configuration %1{/ts}</h2>
      <div class="crm-section">
        <div class="label">{$form.$actvivity_type.label}</div>
        <div class="content">{$form.$actvivity_type.html}</div>
        <div class="clear"></div>
      </div>
      <div class="crm-section">
        <div class="label">{$form.$handlers.label}</div>
        <div class="content">{$form.$handlers.html}</div>
        <div class="clear"></div>
      </div>
  </div>
{/foreach}

<br/>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>

<script type="text/javascript">
var configuration_count = {$configuration_count};
{literal}

/**
 * hide all disabled configs but one
 */
function i3val_hide_extras() {
  var disabled_found = false;
  for (var i = 1; i <= configuration_count; i++) {
    var selector = "select[name='activity_type_id_" + i + "']";
    var value = cj(selector).val();
    if (!value) {
      if (!disabled_found) {
        disabled_found = true;
        cj(selector).closest("div.i3val-config").show();
      } else {
        cj(selector).closest("div.i3val-config").hide();
      }
    } else {
      cj(selector).closest("div.i3val-config").show();
    }
  }
}

// make all empty
cj("select[name^='activity_type_id_']").change(i3val_hide_extras);
i3val_hide_extras();
{/literal}
</script>