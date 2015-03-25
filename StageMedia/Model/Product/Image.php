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

/**
 * Intercepts product image access and downloads original image just-in-time.
 * 
 * Method is slow but only needs to be done once.
 * Requires `allow_url_fopen` permission.
 * 
 * @see http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen
 */
class Knectar_StageMedia_Model_Product_Image extends Mage_Catalog_Model_Product_Image
{

    const REMOTE_URL_CONFIG_PATH = 'system/media_storage_configuration/remote_url';

    /**
     * (non-PHPdoc)
     * @see Mage_Catalog_Model_Product_Image::_fileExists()
     */
    protected function _fileExists($filename)
    {
        if (! parent::_fileExists($filename)) {
            return $this->_downloadRemote($filename);
        }

        return true;
    }

    /**
     * Attempt to fallback to equivalent file, saving at $filename like it was always there.
     * 
     * Returns TRUE if file has been successfully replaced.
     *
     * @param string $filename
     * @return boolean
     */
    protected function _downloadRemote($filename)
    {
        if (! $this->_hasRemoteUrl()) return false;

        $success = false;
        $basepath = Mage::getConfig()->getBaseDir('media') . DS;
        if (preg_match('#^'.preg_quote($basepath).'(.*)$#', $filename, $result)) {
            list(,$localpath) = $result;
            $remotepath = $this->_remoteUrl($localpath);
            Mage::log("Downloading '{$remotepath}'", Zend_Log::INFO);

            // download to original filename
            // TODO use established HTTP client which doesn't depend on allow_url_fopen
            @mkdir(dirname($filename), 0777, true);
            if ($localfile = fopen($filename, 'w')) {
                if ($remotefile = fopen($remotepath, 'r')) {
                    stream_copy_to_stream($remotefile, $localfile);
                    $success = true;

                    fclose($remotefile);
                }
                else {
                    // TODO throw exception so it can be logged in exception.log, might require try..finally which requires PHP 5.5
                    Mage::log("Unable to reach '{$remotepath}'", Zend_Log::ERR);
                }

                fclose($localfile);
                if (! $success) {
                    // file is truncated by fopen, if unsuccessful it is now useless
                    // TODO only overwrite file is successful
                    unlink($localpath);
                }
            }
            else {
                Mage::log("Unable to write to '{$filename}'", Zend_Log::ERR);
            }
        }
        return $success;
    }

    /**
     * Check admin config for supplied URL.
     * 
     * URL is not validated.
     *
     * @return boolean
     */
    protected function _hasRemoteUrl()
    {
        return Mage::getStoreConfigFlag(self::REMOTE_URL_CONFIG_PATH);
    }

    /**
     * Format suitable URL relative to specified remote dir.
     *
     * @param string $localpath
     * @return string
     */
    protected function _remoteUrl($localpath)
    {
        $baseurl = rtrim(Mage::getStoreConfig(self::REMOTE_URL_CONFIG_PATH), DS);
        $localpath = ltrim($localpath, DS);
        return $baseurl . DS . $localpath;
    }
}
