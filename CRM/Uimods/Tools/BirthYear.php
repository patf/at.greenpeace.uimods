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
class CRM_Uimods_Tools_BirthYear {
  /**
   * Get the birth year custom field
   */
  protected static $_birthyear_custom_field = NULL;


  /**
   * process POST hook
   */
  public static function process_post($op, $objectName, $objectId, &$objectRef) {
    // fills the custom field with the correct birth year if someone updates CiviCRM's Birth Date field

    if ($objectRef instanceof CRM_Contact_DAO_Contact) {
      if ((!empty($objectRef->birth_date))
          && ($objectRef->birth_date != 'null')) {
        // Contact Birth date has a value
        try {
          $contactBirthYear = new DateTime($objectRef->birth_date);
        }
        catch (Exception $e) {
          return;
        }

        $birthYearField = self::getCustomField();
        // Update birth year custom field with new value
        $customValues = civicrm_api3('CustomValue', 'create', array(
          'entity_id' => $objectRef->id,
          // Contact birth date to (long) year
          "custom_{$birthYearField['id']}" => $contactBirthYear->format('Y'),
        ));
      }
    }
  }

  /**
   * process CUSTOM hook
   */
  public static function process_custom( $op, $groupID, $entityID, &$params ) {
    // deletes the values CiviCRM's Birth Date field if someone updates the custom field with a year that is contradictory to the birth date

    foreach ($params as $entity) {
      if ($entity['entity_table'] == 'civicrm_contact') {
        $birthYearField = self::getCustomField();
        if (($birthYearField['column_name'] == $entity['column_name'])
          && ($birthYearField['custom_group_id'] == $entity['custom_group_id'])
        ) {
          // birth_year field was written
          // Get value of birth_year field
          $customValues = civicrm_api3('CustomValue', 'get', array(
            'entity_id' => $entity['entity_id'],
            'return.custom_'.$birthYearField['id'] => 1,
          ));
          $birthYear = $customValues['values'][$birthYearField['id']][0];

          // Get contact ID birth date field ($params['entity_id'])
          try {
            $contactBirthDate = civicrm_api3('Contact', 'getsingle', array(
              'return' => "birth_date",
              'id' => $entity['entity_id'],
            ));
          }
          catch (Exception $e) {
            //getsingle throws exception if not found
            return;
          }
          // Contact birth date to year
          if (!empty($contactBirthDate['birth_date'])) {
            try {
              $contactBirthYear = new DateTime($contactBirthDate['birth_date']);
            }
            catch (Exception $e) {
              return;
            }
            // Is birth date = birth year? (Match only long format)
            if ($contactBirthYear->format('Y') != $birthYear) {
              //Delete birth_date
              $result = civicrm_api3('Contact', 'create', array(
                'id' => $entity['entity_id'],
                'birth_date' => '',
              ));
            }
          }
        }
      }
    }
  }


  /**
   * Get the birthyear field (cached)
   *
   * copied from uk.co.mjwconsulting.birthyear
   */
  public static function getCustomField() {
    if (self::$_birthyear_custom_field === NULL) {
      // load custom field data
      self::$_birthyear_custom_field = civicrm_api3('CustomField', 'getsingle', array('name' => "birth_year"));
    }
    return self::$_birthyear_custom_field;
  }

}
