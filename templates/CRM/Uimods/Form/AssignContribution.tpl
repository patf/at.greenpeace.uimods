{*-------------------------------------------------------+
| Greenpeace UI Modifications                            |
| Copyright (C) 2017 SYSTOPIA                            |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*}

{$form.cid.html}

<div>
  <div id="help">
    This will assign the selected contribution to the membership select below.
  </div>

  <div class="crm-section">
    <div class="label">{$form.membership_id.label}</div>
    <div class="content">{$form.membership_id.html}</div>
    <div class="clear"></div>
  </div>

  {if $assigned_to}
  <div id="help">
    This contribution is already assigned to another membership. This connection will be removed if you press 'assign'. A contribution can only be assigned to one membership.
  </div>
  {/if}
<div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
