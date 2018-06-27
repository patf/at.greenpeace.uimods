<?php
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

define('UIMODS_CONFIG',       'at_greenpeace_uimods_config');
define('UIMODS_CONFIG_GROUP', 'at_greenpeace_uimods');

/**
 * Store configuration values
 *
 * @todo initialise and then load from settings
 */
class CRM_Uimods_Config {

  protected $config_data = NULL;
  protected static $singleton = NULL;
  protected static $extended_demographics_group = NULL;
  protected static $incoming_ba_field = NULL;
  protected static $membership_annual_field = NULL;
  protected static $membership_frequency_field = NULL;
  protected static $membership_payment_instrument_field = NULL;
  protected static $membership_contract_number_field = NULL;
  protected static $membership_number_field = NULL;
  protected static $membership_recurring_contribution_field = NULL;

  protected static $payment_frequencies = NULL;
  protected static $payment_instruments = NULL;

  /**
   * Internal constructor
   */
  protected function __construct() {
    // self::updateConfig(); // only for debugging
    $this->config_data = civicrm_api3('Setting', 'getvalue', array('name' => UIMODS_CONFIG, 'group' => UIMODS_CONFIG_GROUP));

    if (empty($this->config_data)) {
      // if it's empty, try reloading
      self::updateConfig();
      $this->config_data = civicrm_api3('Setting', 'getvalue', array('name' => UIMODS_CONFIG, 'group' => UIMODS_CONFIG_GROUP));
    }
  }

  /**
   * get the singleton instance
   */
  public static function getSingleton() {
    if (self::$singleton == NULL) {
      self::$singleton = new CRM_Uimods_Config();
    }
    return self::$singleton;
  }

  /**
   * get a list of fields that contain links to bank accounts
   *
   * @return array(custom_group_id => array(field_id))
   */
  public function getAccountCustomFields() {
    return $this->config_data['account_custom_fields'];
  }

  /**
   * get a list of payment_instruments (id => label)
   */
  public static function getPaymentInstruments() {
    if (self::$payment_instruments === NULL) {
      $payment_instruments = array();
      $payment_instrument_query = civicrm_api3('OptionValue', 'get', array(
        'return'          => 'label,value',
        'option_group_id' => 'payment_instrument',
        'options'         => array('limit' => 0),
      ));
      foreach ($payment_instrument_query['values'] as $instrument) {
        $payment_instruments[$instrument['value']] = $instrument['label'];
      }
      self::$payment_instruments = $payment_instruments;
    }
    return self::$payment_instruments;
  }

  /**
   * get a list of payment_frequencies (id => label)
   */
  public static function getPaymentFrequencies() {
    if (self::$payment_frequencies === NULL) {
      $payment_frequencies = array();
      $payment_frequency_query = civicrm_api3('OptionValue', 'get', array(
        'return'          => 'label,value',
        'option_group_id' => 'payment_frequency',
        'options'         => array('limit' => 0),
      ));
      foreach ($payment_frequency_query['values'] as $frequency) {
        $payment_frequencies[$frequency['value']] = $frequency['label'];
      }
      self::$payment_frequencies = $payment_frequencies;
    }
    return self::$payment_frequencies;
  }

  /**
   * Get the custom_xx field name for the contribution's incoming bank account
   *
   * @return string custom_xx
   */
  public static function getIncomingBAField() {
    if (self::$incoming_ba_field === NULL) {
      self::$incoming_ba_field = civicrm_api3('CustomField', 'getvalue', array(
        'return'          => 'id',
        'name'            => 'from_ba',
        'custom_group_id' => 'contribution_information'));
    }
    return 'custom_' . self::$incoming_ba_field;
  }

  /**
   * Get the custom_xx field name for the contribution's incoming bank account
   *
   * @return string custom_xx
   */
  public static function getMembershipNumberField() {
    if (self::$membership_number_field === NULL) {
      self::$membership_number_field = civicrm_api3('CustomField', 'getvalue', array(
        'return'          => 'id',
        'name'            => 'membership_reference',
        'custom_group_id' => 'membership_general'));
    }
    return 'custom_' . self::$membership_number_field;
  }


  /**
   * Get the custom_xx field name for the membership's annual field
   *
   * @return string custom_xx
   */
  public static function getMembershipAnnualField() {
    if (self::$membership_annual_field === NULL) {
      self::$membership_annual_field = civicrm_api3('CustomField', 'getvalue', array(
        'return'          => 'id',
        'name'            => 'membership_annual',
        'custom_group_id' => 'membership_payment'));
    }
    return 'custom_' . self::$membership_annual_field;
  }

  /**
   * Get the custom_xx field name for the membership's annual field
   *
   * @return string custom_xx
   */
  public static function getMembershipPaymentInstrumentField() {
    if (self::$membership_payment_instrument_field === NULL) {
      self::$membership_payment_instrument_field = civicrm_api3('CustomField', 'getvalue', array(
        'return'          => 'id',
        'name'            => 'payment_instrument',
        'custom_group_id' => 'membership_payment'));
    }
    return 'custom_' . self::$membership_payment_instrument_field;
  }


  /**
   * Get the custom_xx field name for the membership's Contract Number
   *
   * @return string custom_xx
   */
  public static function getMembershipContractNumberField() {
    if (self::$membership_contract_number_field === NULL) {
      self::$membership_contract_number_field = civicrm_api3('CustomField', 'getvalue', array(
        'return'          => 'id',
        'name'            => 'membership_contract',
        'custom_group_id' => 'membership_general'));
    }
    return 'custom_' . self::$membership_contract_number_field;
  }

  /**
   * Get the custom_xx field name for the membership's frequency field
   *
   * @return string custom_xx
   */
  public static function getMembershipFrequencyField() {
    if (self::$membership_frequency_field === NULL) {
      self::$membership_frequency_field = civicrm_api3('CustomField', 'getvalue', array(
        'return'          => 'id',
        'name'            => 'membership_frequency',
        'custom_group_id' => 'membership_payment'));
    }
    return 'custom_' . self::$membership_frequency_field;
  }

  /**
   * Get the custom_xx field name for the membership's recurring contribution
   * field
   *
   * @return string custom_xx
   */
  public static function getRecurringContributionField() {
    if (self::$membership_recurring_contribution_field === NULL) {
      self::$membership_recurring_contribution_field = civicrm_api3('CustomField', 'getvalue', array(
        'return'          => 'id',
        'name'            => 'membership_recurring_contribution',
        'custom_group_id' => 'membership_payment'));
    }
    return 'custom_' . self::$membership_recurring_contribution_field;
  }


  /**
   * (re)generate the config information
   */
  public static function updateConfig() {
    $uimods_config = array();

    // determine custom groups/fields showing bank accounts
    $uimods_config['account_custom_fields'] = array();
    $known_columns = array('to_ba', 'from_ba', 'ch_to_ba', 'ch_from_ba');
    $custom_fields = civicrm_api3('CustomField', 'get', array(
      'column_name' => array('IN' => $known_columns),
      'is_active'   => 1,
      'return'      => "id,custom_group_id",
      ));
    foreach ($custom_fields['values'] as $custom_field) {
      $uimods_config['account_custom_fields'][$custom_field['custom_group_id']][] = $custom_field['id'];
    }

    // finally, store the settings
    // API doesn't work during install: civicrm_api3('Setting', 'create', array(UIMODS_CONFIG => $uimods_config));
    CRM_Core_BAO_Setting::setItem($uimods_config, UIMODS_CONFIG_GROUP, UIMODS_CONFIG);
  }

  /**
   * Get the ID of the "Extended Demographics" custom group
   *
   * @return int ID
   */
  public static function getExtendedDemographicsGroupID() {
    $extended_demographics_group = self::getExtendedDemographicsGroup();
    if ($extended_demographics_group) {
      return $extended_demographics_group['id'];
    } else {
      return 0;
    }
  }

  /**
   * Get the "Extended Demographics" custom group
   *
   * @return array group entity
   */
  public static function getExtendedDemographicsGroup() {
    if (self::$extended_demographics_group == NULL) {
      self::$extended_demographics_group = civicrm_api3('CustomGroup', 'getsingle', array('name' => 'additional_demographics'));
    }
    return self::$extended_demographics_group;
  }

}
