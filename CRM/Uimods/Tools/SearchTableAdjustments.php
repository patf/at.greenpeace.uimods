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

// for contribution table
define('UIMODS_STA_CAMPAIGN_FIELD',           'contribution_source');
define('UIMODS_STA_PAYMENTINSTRUMENT_FIELD',  'payment_instrument');
define('UIMODS_STA_BANKACCOUNT_FIELD',        'product_name');

// for membership search table
define('UIMODS_STA_MEMBERSHIPID_COLUMN',      6);
define('UIMODS_STA_MEMBERSHIPID_FIELD',       'membership_source');
define('UIMODS_STA_MEMBERSHIPPAYMENT_COLUMN', 8);
define('UIMODS_STA_MEMBERSHIPPAYMENT_FIELD',  'payment_mode');
define('UIMODS_STA_CONTRACTNUMBER_FIELD',     'contract_number');

/**
 * Keep birth_date and birth year in sync
 */
class CRM_Uimods_Tools_SearchTableAdjustments {

  /**
   * Modify the contribution search result table (GP-716)
   */
  public static function adjustMembershipTable($objectName, &$headers, &$rows, &$selector) {
    // adjust headers
    $headers[UIMODS_STA_MEMBERSHIPID_COLUMN]['name'] = "ID";
    unset($headers[UIMODS_STA_MEMBERSHIPID_COLUMN]['sort']);
    unset($headers[UIMODS_STA_MEMBERSHIPID_COLUMN]['direction']);

    $headers[UIMODS_STA_MEMBERSHIPPAYMENT_COLUMN]['name'] = "Payment";
    unset($headers[UIMODS_STA_MEMBERSHIPPAYMENT_COLUMN]['sort']);
    unset($headers[UIMODS_STA_MEMBERSHIPPAYMENT_COLUMN]['direction']);

    // collect ids to be loaded
    $membership_ids = array();
    foreach ($rows as $row) {
      if (is_numeric($row['membership_id'])) {
        $membership_ids[] = $row['membership_id'];
      }
    }

    if (empty($membership_ids)) {
      return;
    }

    // load payment data
    $payment_modes = self::getMembershipPaymentModes($membership_ids);

    // manipulate data
    foreach ($rows as &$row) {
      $membership_id = $row['membership_id'];
      if (!empty($payment_modes[$membership_id])) {
        $row[UIMODS_STA_MEMBERSHIPPAYMENT_FIELD] = $payment_modes[$membership_id];
      } else {
        $row[UIMODS_STA_MEMBERSHIPPAYMENT_FIELD] = '';
      }
    }
  }

  /**
   * Adjust the smarty variable for the membership tab (GP-716)
   */
  public static function adjustMembershipTableSmarty() {
    $smarty = CRM_Core_Smarty::singleton();
    $activeMembers   = $smarty->get_template_vars('activeMembers');
    $inActiveMembers = $smarty->get_template_vars('inActiveMembers');
    if (empty($activeMembers) && empty($inActiveMembers)) {
      return;
    }

    // collect membership ids
    $membership_ids = array();
    foreach ($activeMembers as $membership_id => $membership) {
      $membership_ids[] = $membership_id;
    }
    foreach ($inActiveMembers as $membership_id => $membership) {
      $membership_ids[] = $membership_id;
    }

    // load payment data
    $payment_modes = self::getMembershipPaymentModes($membership_ids);

    // load contract numbers
    $contract_numbers = array();
    $contract_number_field = CRM_Uimods_Config::getMembershipContractNumberField();
    $contract_number_query = civicrm_api3('Membership', 'get', array(
      'return'  => "id,{$contract_number_field}",
      'id'      => array('IN' => $membership_ids),
      'options' => array('limit' => 0),
      ));
    foreach ($contract_number_query['values'] as $contract) {
      if (isset($contract[$contract_number_field])) {
        $contract_numbers[$contract['id']] = $contract[$contract_number_field];
      }
    }

    // adjust data
    foreach ($activeMembers as $membership_id => &$membership) {
      $membership[UIMODS_STA_MEMBERSHIPPAYMENT_FIELD] = $payment_modes[$membership_id];

      if (isset($contract_numbers[$membership_id])) {
        $membership[UIMODS_STA_CONTRACTNUMBER_FIELD] = $contract_numbers[$membership_id];
      } else {
        $membership[UIMODS_STA_CONTRACTNUMBER_FIELD] = '';
      }
    }
    foreach ($inActiveMembers as $membership_id => &$membership) {
      $membership[UIMODS_STA_MEMBERSHIPPAYMENT_FIELD] = $payment_modes[$membership_id];
    }

    // re-assign to smarty
    $smarty->assign('activeMembers', $activeMembers);
    $smarty->assign('inActiveMembers', $inActiveMembers);
  }



  /**
   * Modify the contribution search result table (GP-716)
   */
  public static function adjustContributionTable($objectName, &$headers, &$rows, &$selector) {
    // adjust headers
    $campaign_colum = $payment_instrument_colum = $ba_column = $index = -1;
    foreach ($headers as &$header) {
      $index += 1;
      switch (CRM_Utils_Array::value('sort', $header)) {
        case 'contribution_source':
          $header['name'] = "Campaign";
          unset($header['sort']);
          unset($header['direction']);
          $campaign_colum = $index;
          break;

        case 'thankyou_date':
          $header['name'] = "Paid via";
          unset($header['sort']);
          unset($header['direction']);
          $payment_instrument_colum = $index;
          break;

        case 'product_name':
          $header['name'] = "Donor's BA";
          unset($header['sort']);
          unset($header['direction']);
          $ba_column = $index;
          break;

        default:
          break;
      }
    }

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
        'return'  => "title",
        'id'      => array('IN' => $campaign_ids),
        'options' => array('limit' => 0),
      ));
      $campaign_list = $result['values'];
    }

    // load payment instruments
    $payment_instruments = CRM_Uimods_Config::getPaymentInstruments();

    // load bank references
    $bank_account_ids = array();
    foreach ($missing_data['values'] as $contribution) {
      if (!empty($contribution[$incoming_ba_field])) {
        $bank_account_ids[] = (int) $contribution[$incoming_ba_field];
      }
    }
    $baId2reference = self::getBankAccounts($bank_account_ids);

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
      if (!empty($missing_data['values'][$contribution_id]['payment_instrument_id'])) {
        $payment_instrument_id = $missing_data['values'][$contribution_id]['payment_instrument_id'];
        if (!empty($payment_instruments[$payment_instrument_id])) {
          $row[UIMODS_STA_PAYMENTINSTRUMENT_FIELD] = $payment_instruments[$payment_instrument_id];
        } else {
          $row[UIMODS_STA_PAYMENTINSTRUMENT_FIELD] = 'n/a';
        }
      } else {
        $row[UIMODS_STA_PAYMENTINSTRUMENT_FIELD] = '';
      }
    }
  }

  /**
   * Calculate a string representation of the payment mode,
   *  e.g. "€120.00<br/>(€10.00 monthly)"
   *
   * @return array membershipID => string
   */
  protected static function getMembershipPaymentModes($membership_ids) {
    $payment_modes = array();
    if (empty($membership_ids)) {
      return $payment_modes;
    }

    // find the fields
    $annual_field    = CRM_Uimods_Config::getMembershipAnnualField();
    $frequency_field = CRM_Uimods_Config::getMembershipFrequencyField();
    $pi_field        = CRM_Uimods_Config::getMembershipPaymentInstrumentField();

    // load some lists
    $payment_instruments = CRM_Uimods_Config::getPaymentInstruments();
    $payment_frequencies = CRM_Uimods_Config::getPaymentFrequencies();

    // then: load the custom fields
    $membership_query = civicrm_api3('Membership', 'get', array(
      'id'      => array('IN' => $membership_ids),
      'return'  => "id,{$annual_field},{$frequency_field},{$pi_field}",
      'options' => array('limit' => 0)));

    foreach ($membership_query['values'] as $membership) {
      if (empty($membership[$annual_field])) continue;
      $annual_amount = $membership[$annual_field];
      $payment_mode = CRM_Utils_Money::format($annual_amount);
      if (!empty($membership[$frequency_field]) && $membership[$frequency_field] > 1) {
        $frequency     = $membership[$frequency_field];
        if (!empty($membership[$pi_field]) && !empty($payment_instruments[$membership[$pi_field]])) {
          $payment_instrument = $payment_instruments[$membership[$pi_field]];
          $payment_mode .= " ({$payment_instrument})";
        }
        $payment_mode .= "<br/>(";
        $payment_mode .= CRM_Utils_Money::format($annual_amount/$frequency);
        $payment_mode .= " {$payment_frequencies[$frequency]})";
      }
      $payment_mode = str_replace(' ', '&nbsp;', $payment_mode); // avoid linebreaks
      $payment_modes[$membership['id']] = $payment_mode;
    }

    return $payment_modes;
  }


  /**
   * generate a list of IBAN bank accounts
   */
  public static function getBankAccounts($bank_account_ids) {
    $baId2reference = array();
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
    return $baId2reference;
  }
}

