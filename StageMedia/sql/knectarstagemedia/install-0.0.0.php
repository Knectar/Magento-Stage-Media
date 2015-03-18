<?php

// Ideally this should be in 'data' dir but 'sql' executes sooner

/* @var $this Mage_Core_Model_Resource_Setup */
header('Content-Type: text/plain');

// core_config_data has not yet loaded so Mage::getStoreConfig will not work
$config = Mage::helper('knectarstagemedia')->getAllStoreConfig();

$unsecureBaseUrl = @$config['web/unsecure/base_url'];
$secureBaseUrl = @$config['web/secure/base_url'];

$request = Mage::app()->getRequest();
$baseUrl = ($request->isSecure() ? 'https://' : 'http://');
$baseUrl .= $request->getHttpHost(false);
$baseUrl .= dirname(@$_SERVER['SCRIPT_NAME']); // best guess of root dir

// check against both bases because one of them is probably the wrong protocol
if (($baseUrl === $unsecureBaseUrl) || ($baseUrl === $secureBaseUrl)) {
    // nothing to do, end this install script
    return;
}

$replace = array(
    '{{unsecure_base_url}}' => $unsecureBaseUrl,
    '{{secure_base_url}}' => $secureBaseUrl,
    '{{base_url}}' => $baseUrl
);

$remoteUrl = strtr(@$config['web/unsecure/base_media_url'], $replace);
Mage::getConfig()->saveConfig('system/media_storage_configuration/remote_url', $remoteUrl);
Mage::getConfig()->saveConfig('web/unsecure/base_url', $baseUrl);
Mage::getConfig()->saveConfig('web/secure/base_url', $baseUrl);

// overwrite current store in case global value is already overridden
// current store is probably default store in a stage environment
$storeId = Mage::app()->getStore()->getId();
Mage::getConfig()->saveConfig('web/unsecure/base_url', $baseUrl, 'stores', $storeId);
Mage::getConfig()->saveConfig('web/secure/base_url', $baseUrl, 'stores', $storeId);
