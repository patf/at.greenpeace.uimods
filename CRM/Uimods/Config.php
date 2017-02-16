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
 
/**
 * Store configuration values
 *
 * @todo initialise and then load from settings
 */
class CRM_Uimods_Config {

  /**
   * get a list of fields that contain links to bank accounts
   *
   * @return array(custom_group_id => array(field_id))
   */

  public static function getAccountCustomFields() {
    // TODO: make dynamic
    return array(
      10 => array(23, 24),
      16 => array(47, 48),
    );
  }
}
