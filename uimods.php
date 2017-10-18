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

require_once 'uimods.civix.php';

/**
 * Implements hook_civicrm_pre()
 */
function uimods_civicrm_pre($op, $objectName, $id, &$params) {
  // GP-815: for newly created contacts:
  if ($op == 'create' && !$id && ($objectName == 'Individual' || $objectName == 'Organization')) {
    // ...set preferred language to German
    $params['preferred_language'] = 'de_DE';
  }
}

/**
 * Implements hook_civicrm_links()
 */
function uimods_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ($op=='contribution.selector.row') {
    // add a Contract link to contributions that are connected to memberships
    $contribution_id = (int) $objectId;
    if ($contribution_id) {
      // add 'view contract' link
      $membership_id = CRM_Core_DAO::singleValueQuery("SELECT membership_id FROM civicrm_membership_payment WHERE contribution_id = {$contribution_id} LIMIT 1");
      if ($membership_id) {
        $contact_id = CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM civicrm_membership WHERE id = {$membership_id} LIMIT 1");
        if ($contact_id) {
          $links[] = array(
            'name'  => 'Contract',
            'title' => 'View Contract',
            'url'   => 'civicrm/contact/view/membership',
            'qs'    => "reset=1&id={$membership_id}&cid={$contact_id}&action=view");
        }
      }

      // add 'assign to contract' link
      if (CRM_Core_Permission::check('edit contributions')) {
        $financial_type_id = CRM_Core_DAO::singleValueQuery("SELECT financial_type_id FROM civicrm_contribution WHERE id = {$contribution_id} LIMIT 1");
        if ($financial_type_id == 2) {
          // only for membership dues
          $links[] = array(
            'name'  => 'Assign',
            'title' => 'Assign to Contract',
            'url'   => 'civicrm/contribution/assign',
            'qs'    => "reset=1&cid={$contribution_id}");
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_post()
 */
function uimods_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  CRM_Uimods_Tools_BirthYear::process_post($op, $objectName, $objectId, $objectRef);
}

/**
 * Implements hook_civicrm_custom
 */
function uimods_civicrm_custom( $op, $groupID, $entityID, &$params ) {
  CRM_Uimods_Tools_BirthYear::process_custom($op, $groupID, $entityID, $params);
}

/**
 * Implements hook_civicrm_searchColumns
 */
function uimods_civicrm_searchColumns( $objectName, &$headers, &$rows, &$selector ) {
  if ($objectName == 'contribution') {
    CRM_Uimods_Tools_SearchTableAdjustments::adjustContributionTable($objectName, $headers, $rows, $selector);
  } elseif ($objectName == 'membership') {
    CRM_Uimods_Tools_SearchTableAdjustments::adjustMembershipTable($objectName, $headers, $rows, $selector);
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function uimods_civicrm_buildForm($formName, &$form) {
  // hook in the various renderers
  CRM_Uimods_Tools_BankAccount::renderForm($formName, $form);
  CRM_Uimods_Tools_BirthYear::process_buildForm($formName, $form);
  if ($formName == "CRM_Contact_Form_Merge") {
    require_once 'CRM/Uimods/MergeFormUIMods.php';
    CRM_Uimods_MergeFromUIMods::buildFormHook($formName, $form);
  }
}

/**
 * Implements hook_civicrm_alterTemplateFile().
 *
 * Use modified templates for Membership and Contribution lists
 * If they stop working (after a CiviCRM upgrade):
 *  1) check if they have changed (compare checksums). If so:
 *  2) create a diff of our files vs. the original file (4.6.22)
 *  3) try to apply (patch) the original files and copy to extension
 */
function uimods_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {
  // MEMBERSHIPS
  if ($tplName == 'CRM/Member/Form/Search.tpl') {
    // modified version based on CiviCRM 4.6.22     SHA1: 152779bfc8fb9e3cffcc3ed903673bbc4a773ee7
    //  also replaced CRM/Member/Form/Selector.tpl: SHA1: e91bc448a67142258d27fb1deef05284f0a25aa6
    $tplName = 'CRM/Member/Form/UimodsSearch.tpl';
  } elseif ($tplName == 'CRM/Member/Page/Tab.tpl') {
    CRM_Uimods_Tools_SearchTableAdjustments::adjustMembershipTableSmarty();
    // modified version based on CiviCRM 4.6.22 -   SHA1: fa69538de32175029221af5583c25b3c607b5c22
    $tplName = 'CRM/Member/Page/UimodsTab.tpl';

  // CONTRIBUTIONS
  } elseif ($tplName == 'CRM/Contribute/Form/Search.tpl') {
    // modified version based on CiviCRM 4.6.22 -       SHA1: b885eb162c82557ed87535a7b940397492af12e4
    //  also replaced CRM/Contribute/Form/Selector.tpl: SHA1: 70ecb4911dfd57f685be1048f11feb3463d850de
    $tplName = 'CRM/Contribute/Form/UimodsSearch.tpl';
  } elseif ($tplName == 'CRM/Contribute/Page/Tab.tpl') {
    // modified version based on CiviCRM 4.6.22         SHA1: 9f82712218a9a19aabfc0906c4afbcd6faf19ee7
    $tplName = 'CRM/Contribute/Page/UimodsTab.tpl';
  }
}

/**
 * implement the hook to customize the summary view
 */
function uimods_civicrm_pageRun( &$page ) {
  $page_name = $page->getVar('_name');
  if ($page_name == 'CRM_Contact_Page_View_Summary') {
      $script = file_get_contents(__DIR__ . '/js/summary_view.js');
      $script = str_replace('EXTENDED_DEMOGRAPHICS', CRM_Uimods_Config::getExtendedDemographicsGroupID(), $script);
      CRM_Core_Region::instance('page-header')->add(array(
        'script' => $script,
        ));
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function uimods_civicrm_config(&$config) {
  _uimods_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function uimods_civicrm_xmlMenu(&$files) {
  _uimods_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function uimods_civicrm_install() {
  _uimods_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function uimods_civicrm_uninstall() {
  _uimods_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function uimods_civicrm_enable() {
  _uimods_civix_civicrm_enable();

  // update config
  include_once('CRM/Uimods/Config.php');
  CRM_Uimods_Config::updateConfig();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function uimods_civicrm_disable() {
  _uimods_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function uimods_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _uimods_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function uimods_civicrm_managed(&$entities) {
  _uimods_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function uimods_civicrm_caseTypes(&$caseTypes) {
  _uimods_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function uimods_civicrm_angularModules(&$angularModules) {
_uimods_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function uimods_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _uimods_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
