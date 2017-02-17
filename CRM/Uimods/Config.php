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

define('UIMODS_CONFIG', 'at.greenpeace.uimods');
 
/**
 * Store configuration values
 *
 * @todo initialise and then load from settings
 */
class CRM_Uimods_Config {

  protected $config_data = NULL;
  protected static $singleton = NULL;

  /** 
   * Internal constructor
   */
  protected function __construct() {
    // self::updateConfig(); // only for debugging
    $this->config_data = civicrm_api3('Setting', 'getvalue', array('name' => UIMODS_CONFIG));

    if (empty($this->config_data)) {
      // if it's empty, try reloading
      self::updateConfig();
      $this->config_data = civicrm_api3('Setting', 'getvalue', array('name' => UIMODS_CONFIG));
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
    civicrm_api3('Setting', 'create', array(UIMODS_CONFIG => $uimods_config));
  }

}