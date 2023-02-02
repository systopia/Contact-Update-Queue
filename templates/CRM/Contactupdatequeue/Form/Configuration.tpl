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
+-------------------------------------------------------*}

<h3>{ts domain="be.aivl.i3val"}Processing Options{/ts}</h3>

<div>
  <div class="crm-section">
    <div class="label">{$form.quickhistory.label}</div>
    <div class="content">{$form.quickhistory.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.default_action.label}</div>
    <div class="content">{$form.default_action.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.flag_status.label}</div>
    <div class="content">{$form.flag_status.html}</div>
    <div class="clear"></div>
  </div>

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

<h3>{ts domain="be.aivl.i3val"}Update Request Options{/ts}</h3>
<div>
  <div class="crm-section">
    <div class="label">{$form.allow_clearing.label}&nbsp;<a onclick='CRM.help("{ts domain="be.aivl.i3val"}Clearing Fields{/ts}", {literal}{"id":"id-field-clearing","file":"CRM\/I3val\/Form\/Configuration"}{/literal}); return false;' href="#" title="{ts domain="be.aivl.i3val"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.allow_clearing.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.strip_chars.label}&nbsp;<a onclick='CRM.help("{ts domain="be.aivl.i3val"}Strip Characters{/ts}", {literal}{"id":"id-strip-chars","file":"CRM\/I3val\/Form\/Configuration"}{/literal}); return false;' href="#" title="{ts domain="be.aivl.i3val"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.strip_chars.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section">
    <div class="label">{$form.empty_token.label}&nbsp;<a onclick='CRM.help("{ts domain="be.aivl.i3val"}Empty Token{/ts}", {literal}{"id":"id-empty-token","file":"CRM\/I3val\/Form\/Configuration"}{/literal}); return false;' href="#" title="{ts domain="be.aivl.i3val"}Help{/ts}" class="helpicon">&nbsp;</a></div>
    <div class="content">{$form.empty_token.html}</div>
    <div class="clear"></div>
  </div>
</div>

<br/>

<br/>
<h3>{ts domain="be.aivl.i3val"}Data Configuration{/ts}</h3>

{foreach from=$configurations item=index}
  {capture assign=actvivity_type}activity_type_id_{$index}{/capture}
  {capture assign=handlers}handler_classes_{$index}{/capture}
  <div class="contactupdatequeue-config">
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
function contactupdatequeue_hide_extras() {
  var disabled_found = false;
  for (var i = 1; i <= configuration_count; i++) {
    var selector = "select[name='activity_type_id_" + i + "']";
    var value = cj(selector).val();
    if (!value) {
      if (!disabled_found) {
        disabled_found = true;
        cj(selector).closest("div.contactupdatequeue-config").show();
      } else {
        cj(selector).closest("div.contactupdatequeue-config").hide();
      }
    } else {
      cj(selector).closest("div.contactupdatequeue-config").show();
    }
  }
}

// make all empty
cj("select[name^='activity_type_id_']").change(contactupdatequeue_hide_extras);
contactupdatequeue_hide_extras();
{/literal}
</script>
