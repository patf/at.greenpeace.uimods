<?php
/*-------------------------------------------------------+
| Greenpeace UI Modifications                            |
| Copyright (C) 2017 SYSTOPIA                            |
| Author    Matthew Wire (mjw@mjwconsult.co.uk)          |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

/**
 * Keep birth_date and birth year in sync
 */
class CRM_Uimods_Tools_SearchTableAdjustments {

  /**
   *
   */
  public static function adjustContributionTable($objectName, &$headers, &$rows, &$selector) {
    // adjuste headers
    $headers[2]['name'] = "Campaign";
    $headers[4]['name'] = "Paid By";
    $headers[6]['name'] = "Donor's BA";
    unset($headers[2]['sort']);
    unset($headers[4]['sort']);
    unset($headers[6]['sort']);

    // collect ids to be loaded
    $campaign_ids = array();
    foreach ($rows as $row) {
      if (!empty($row['campaign_id'])) {
        $campaign_ids[] = $row['campaign_id'];
      }
    }

    // load data
    $campaign_list = array();
    if (!empty($campaign_ids)) {
      $result = civicrm_api3('Campaign', 'get', array(
        'return' => "title",
        'id'     => array('IN' => $campaign_ids),
      ));
      $campaign_list = $result['values'];
    }

    // load payment instruments
    $payment_instruments = array();
    $payment_instrument_query = civicrm_api3('OptionValue', 'get', array(
      'return' => "label,value",
      'option_group_id' => "payment_instrument",
      'options' => array('limit' => 0),
    ));
    foreach ($payment_instrument_query['values'] as $instrument) {
      $payment_instruments[$instrument['value']] = $instrument['label'];
    }

    // manipulate data
    foreach ($rows as &$row) {
      if (!empty($row['campaign_id'])) {
        $campaign = $campaign_list[$row['campaign_id']];
        $link = CRM_Utils_System::url("civicrm/a/#/campaign/{$row['campaign_id']}/view");
        $row['contribution_source'] = "<a href='{$link}'>{$campaign['title']}</a>";
      } else {
        $row['contribution_source'] = '';
      }
    }
  }
}
