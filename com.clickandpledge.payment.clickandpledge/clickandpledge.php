<?php

require_once 'clickandpledge.civix.php';

/**
 * Implementation of hook_civicrm_config().
 */
function clickandpledge_civicrm_config(&$config) {
  _clickandpledge_civix_civicrm_config($config);
  $extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR;
  $include_path = $extRoot . PATH_SEPARATOR . get_include_path( );
  set_include_path( $include_path );
}

/**
 * Implementation of hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 */
function clickandpledge_civicrm_xmlMenu(&$files) {
  _clickandpledge_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install().
 */
function clickandpledge_civicrm_install() {
  // Create required tables for clickandpledge.
  require_once "CRM/Core/DAO.php";
  CRM_Core_DAO::executeQuery("
  CREATE TABLE IF NOT EXISTS `civicrm_clickandpledge_customers` (
    `email` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
    `id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `is_live` tinyint(4) NOT NULL COMMENT 'Whether this is a live or test transaction',
    `processor_id` int(10) DEFAULT NULL COMMENT 'ID from civicrm_payment_processor',
     UNIQUE KEY `id` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  ");

  CRM_Core_DAO::executeQuery("
  CREATE TABLE IF NOT EXISTS `civicrm_clickandpledge_plans` (
    `plan_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `is_live` tinyint(4) NOT NULL COMMENT 'Whether this is a live or test transaction',
    `processor_id` int(10) DEFAULT NULL COMMENT 'ID from civicrm_payment_processor',
     UNIQUE KEY `plan_id` (`plan_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  ");

  CRM_Core_DAO::executeQuery("
  CREATE TABLE IF NOT EXISTS `civicrm_clickandpledge_subscriptions` (
    `customer_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `invoice_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `end_time` int(11) NOT NULL DEFAULT '0',
    `is_live` tinyint(4) NOT NULL COMMENT 'Whether this is a live or test transaction',
    `processor_id` int(10) DEFAULT NULL COMMENT 'ID from civicrm_payment_processor',
    KEY `end_time` (`end_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  ");

  return _clickandpledge_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall().
 */
function clickandpledge_civicrm_uninstall() {
  // Remove clickandpledge tables on uninstall.
  require_once "CRM/Core/DAO.php";
  CRM_Core_DAO::executeQuery("DROP TABLE civicrm_clickandpledge_customers");
  CRM_Core_DAO::executeQuery("DROP TABLE civicrm_clickandpledge_plans");
  CRM_Core_DAO::executeQuery("DROP TABLE civicrm_clickandpledge_subscriptions");

  return _clickandpledge_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable().
 */
function clickandpledge_civicrm_enable() {
   return _clickandpledge_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable().
 */
function clickandpledge_civicrm_disable() {
  return _clickandpledge_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function clickandpledge_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _clickandpledge_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_validateForm().
 *
 * Prevent server validation of cc fields
 *
 * @param $formName - the name of the form
 * @param $fields - Array of name value pairs for all 'POST'ed form values
 * @param $files - Array of file properties as sent by PHP POST protocol
 * @param $form - reference to the form object
 * @param $errors - Reference to the errors array.
 */
function clickandpledge_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if (empty($form->_paymentProcessor['payment_processor_type'])) {
    return;
  }
  // If clickandpledge is active here.
  if (isset($form->_elementIndex['clickandpledge_token'])) {
    if ($form->elementExists('credit_card_number')) {
      $cc_field = $form->getElement('credit_card_number');
      $form->removeElement('credit_card_number', true);
      $form->addElement($cc_field);
    }
    if ($form->elementExists('cvv2')) {
      $cvv2_field = $form->getElement('cvv2');
      $form->removeElement('cvv2', true);
      $form->addElement($cvv2_field);
    }
  }
}

/**
 * Implementation of hook_civicrm_buildForm().
 *
 * @param $formName - the name of the form
 * @param $form - reference to the form object
 */
function clickandpledge_civicrm_buildForm($formName, &$form) {
  $clickandpledge_key = clickandpledge_get_key($form);
  // If this is not a form clickandpledge is involved in, do nothing.
  if (empty($clickandpledge_key)) {
    return;
  }
  $params = $form->get('params');
  // Contrib forms store this in $params, Event forms in $params[0].
  if (!empty($params[0]['clickandpledge_token'])) {
    $params = $params[0];
  }
  $clickandpledge_token = (empty($params['clickandpledge_token']) ? NULL : $params['clickandpledge_token']);

  // Add some hidden fields for clickandpledge.
  if (!$form->elementExists('clickandpledge_token')) {
    $form->setAttribute('class', $form->getAttribute('class') . ' clickandpledge-payment-form');
    $form->addElement('hidden', 'clickandpledge_token', $clickandpledge_token, array('id' => 'clickandpledge-token'));
  }
  clickandpledge_add_clickandpledge_js($clickandpledge_key, $form);

  // Add email field as it would usually be found on donation forms.
  if (!isset($form->_elementIndex['email']) && !empty($form->userEmail)) {
    $form->addElement('hidden', 'email', $form->userEmail, array('id' => 'user-email'));
  }
}

/**
 * Return the clickandpledge api public key (aka password)
 *
 * If this form could conceiveably now or at any time in the future
 * contain a clickandpledge payment processor, return the api public key for
 * that processor.
 */
function clickandpledge_get_key($form) {
  if (empty($form->_paymentProcessor)) {
    return;
  }
  // Only return first value if clickandpledge is the only/default.
  if ($form->_paymentProcessor['payment_processor_type'] == 'Click & Pledge') {
    if (isset($form->_paymentProcessor['password'])) {
      return $form->_paymentProcessor['password'];
    }
  }

  // Otherwise we need to look through all active payprocs and find clickandpledge.
  $is_test = 0;
  if (isset($form->_mode)) {
    $is_test = $form->_mode == 'live' ? 0 : 1;
  }

  // The _paymentProcessors array seems to be the most reliable way to find
  // if the form is using clickandpledge.
  if (!empty($form->_paymentProcessors)) {
    foreach ($form->_paymentProcessors as $pp) {
      if ($pp['payment_processor_type'] == 'Click & Pledge') {
        if (!empty($pp['password'])) {
          return $pp['password'];
        }
        // We have a match.
        return clickandpledge_get_key_for_name($pp['name'], $is_test);
      }
    }
  }
  // Return NULL if this is not a form with clickandpledge involved.
  return NULL;
}

/**
 * Given a payment processor name, return the pub key.
 */
function clickandpledge_get_key_for_name($name, $is_test) {
  try {
    $params = array('name' => $name, 'is_test' => $is_test);
    $results = civicrm_api3('PaymentProcessor', 'get', $params);
    if ($results['count'] == 1) {
      $result = array_pop($results['values']);
      return $result['password'];
    }
  }
  catch (CiviCRM_API3_Exception $e) {
    return NULL;
  }
}

/**
 * Add publishable key and event bindings for clickandpledge.js.
 */
function clickandpledge_add_clickandpledge_js($clickandpledge_key, $form) {
  $form->addElement('text', 'clickandpledge_pub_key', $clickandpledge_key, array('id' => 'clickandpledge-pub-key'));
  CRM_Core_Resources::singleton()->addScriptFile('com.clickandpledge.payment.clickandpledge', 'js/civicrm_clickandpledge.js', 0);
}

/**
 * Implementation of hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
 
function clickandpledge_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'com.clickandpledge.payment.clickandpledge',
    'name' => 'Click & Pledge',
    'entity' => 'PaymentProcessorType',
    'params' => array(
    'version' => 3,
    'name' => 'Click & Pledge',
    'title' => 'Click & Pledge',
    'description' => 'clickandpledge Payment Processor',
    'class_name' => 'Payment_ClickandPledge',
    'billing_mode' => 'form',
    'user_name_label' => 'Account ID',
    'password_label' => 'API Account GUID',
	'signature_label' => 'Connect Campaign URL Alias',
    'url_site_default' => 'https://manual.clickandpledge.com/CiviCRM-WordPress.html',
    'url_site_test_default' => 'https://forums.clickandpledge.com/forum/platform-product-forums/3rd-party-integrations/civicrm/wordpress-integration',
	'url_recur_default' => 'https://manual.clickandpledge.com/CiviCRM-WordPress.html',
	'url_recur_test_default' => 'https://forums.clickandpledge.com/forum/platform-product-forums/3rd-party-integrations/civicrm/wordpress-integration',
    'is_recur' => 1,
    'payment_type' => 1
    ),
  );

  return _clickandpledge_civix_civicrm_managed($entities);
}
