<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Knectar Design
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 *
 * @category Utilities
 * @package Knectar_StageMedia
 * @author Daniel Deady <daniel.deady@knectar.com>
 * @license http://opensource.org/licenses/MIT
 */

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
