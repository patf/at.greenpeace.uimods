/*-------------------------------------------------------+
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
+--------------------------------------------------------*/

// GP-1090
// click the membership new checkbox
cj("#operation\\[move_rel_table_memberships\\]\\[add\\]").prop('checked', true);
// disable the checkbox afterwards!
cj("#operation\\[move_rel_table_memberships\\]\\[add\\]").attr('disabled', 'disabled');
// FixMe: Not the cleanest way, but apparently checkboxes can't be set to readonly, and disabled doesn't transfer the value
cj("#operation\\[move_rel_table_memberships\\]\\[add\\]").after('<input name="operation[move_rel_table_memberships][add]" type="hidden" value="1" />');

// Hide summary fields header
CRM.$('tr:contains("Summary Fields")').hide();

// hide unwanted custom fields
CRM.vars.hiddenCustomFields.forEach(function(field) {
  CRM.$('tr:has(input[name="move_' + field + '"])').hide();
});
