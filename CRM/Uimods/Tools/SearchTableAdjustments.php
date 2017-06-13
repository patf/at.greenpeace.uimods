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

define('UIMODS_STA_CAMPAIGN_COLUMN',          2);
define('UIMODS_STA_CAMPAIGN_FIELD',           'contribution_source');
define('UIMODS_STA_PAYMENTINSTRUMENT_COLUMN', 4);
define('UIMODS_STA_PAYMENTINSTRUMENT_FIELD',  'thankyou_date');
define('UIMODS_STA_BANKACCOUNT_COLUMN',       6);
define('UIMODS_STA_BANKACCOUNT_FIELD',        'product_name');

/**
 * Keep birth_date and birth year in sync
 */
class CRM_Uimods_Tools_SearchTableAdjustments {
  /**
   * Modify the contribution search result table (GP-716)
   */
  public static function adjustContributionTable($objectName, &$headers, &$rows, &$selector) {
    // adjuste headers
    $headers[UIMODS_STA_CAMPAIGN_COLUMN]['name']          = "Campaign";
    $headers[UIMODS_STA_PAYMENTINSTRUMENT_COLUMN]['name'] = "Paid By";
    $headers[UIMODS_STA_BANKACCOUNT_COLUMN]['name']       = "Donor's BA";
    unset($headers[UIMODS_STA_CAMPAIGN_COLUMN]['sort']);
    unset($headers[UIMODS_STA_CAMPAIGN_COLUMN]['direction']);
    unset($headers[UIMODS_STA_PAYMENTINSTRUMENT_COLUMN]['sort']);
    unset($headers[UIMODS_STA_PAYMENTINSTRUMENT_COLUMN]['direction']);
    unset($headers[UIMODS_STA_BANKACCOUNT_COLUMN]['sort']);
    unset($headers[UIMODS_STA_BANKACCOUNT_COLUMN]['direction']);

    // collect ids to be loaded
    $contribution_ids = array();
    $campaign_ids = array();
    foreach ($rows as $row) {
      if (is_numeric($row['contribution_id'])) {
        $contribution_ids[] = $row['contribution_id'];
      }
      if (is_numeric($row['campaign_id'])) {
        $campaign_ids[] = $row['campaign_id'];
      }
    }

    // no contributions -> no action
    if (empty($contribution_ids)) return;

    // load the data missing in the rows as we get it
    $incoming_ba_field = CRM_Uimods_Config::getIncomingBAField();
    $missing_data = civicrm_api3('Contribution', 'get', array(
      'return'  => "id,payment_instrument_id,{$incoming_ba_field}",
      'id'      => array('IN' => $contribution_ids),
      'options' => array('limit' => 0),
      ));

    // load campaigns
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
      'return'          => 'label,value',
      'option_group_id' => 'payment_instrument',
      'options'         => array('limit' => 0),
    ));
    foreach ($payment_instrument_query['values'] as $instrument) {
      $payment_instruments[$instrument['value']] = $instrument['label'];
    }

    // load bank references
    $bank_account_ids = array();
    $baId2reference = array();
    foreach ($missing_data['values'] as $contribution) {
      if (!empty($contribution[$incoming_ba_field])) {
        $bank_account_ids[] = (int) $contribution[$incoming_ba_field];
      }
    }
    if (!empty($bank_account_ids)) {
      $iban_type = civicrm_api3('OptionValue', 'getvalue', array(
        'option_group_id' => 'civicrm_banking.reference_types',
        'return'          => 'id',
        'value'           => 'IBAN'));
      $ba_reference_query = civicrm_api3('BankingAccountReference', 'get', array(
        'ba_id'             => array('IN' => $bank_account_ids),
        'reference_type_id' => $iban_type,
        'options'           => array('limit' => 0),
        'return'            => 'ba_id,reference'));
      foreach ($ba_reference_query['values'] as $reference) {
        $baId2reference[$reference['ba_id']] = $reference['reference'];
      }
    }


    // manipulate data
    foreach ($rows as &$row) {
      $contribution_id = $row['contribution_id'];

      // set campaign
      if (!empty($row['campaign_id'])) {
        $campaign = $campaign_list[$row['campaign_id']];
        $link = CRM_Utils_System::url("civicrm/a/#/campaign/{$row['campaign_id']}/view");
        $row[UIMODS_STA_CAMPAIGN_FIELD] = "<a href='{$link}'>{$campaign['title']}</a>";
      } else {
        $row[UIMODS_STA_CAMPAIGN_FIELD] = '';
      }

      // set bank account
      if (!empty($missing_data['values'][$contribution_id][$incoming_ba_field])) {
        $ba_id = $missing_data['values'][$contribution_id][$incoming_ba_field];
        if (!empty($baId2reference[$ba_id])) {
          $row[UIMODS_STA_BANKACCOUNT_FIELD] = $baId2reference[$ba_id];
        } else {
          $row[UIMODS_STA_BANKACCOUNT_FIELD] = 'n/a';
        }
      } else {
        $row[UIMODS_STA_BANKACCOUNT_FIELD] = '';
      }

      // set payment instrument
      // NEED template change
      // if (!empty($missing_data['values'][$contribution_id]['payment_instrument_id'])) {
      //   $payment_instrument_id = $missing_data['values'][$contribution_id]['payment_instrument_id'];
      //   if (!empty($payment_instruments[$payment_instrument_id])) {
      //     $row[UIMODS_STA_PAYMENTINSTRUMENT_FIELD] = $payment_instruments[$payment_instrument_id];
      //   } else {
      //     $row[UIMODS_STA_PAYMENTINSTRUMENT_FIELD] = 'n/a';
      //   }
      // } else {
      //   $row[UIMODS_STA_PAYMENTINSTRUMENT_FIELD] = '';
      // }
    }
  }
}
