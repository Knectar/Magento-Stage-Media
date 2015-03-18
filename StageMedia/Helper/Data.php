<?php

class Knectar_StageMedia_Helper_Data extends Mage_Core_Helper_Data
{

    public function getAllStoreConfig($filter = 'web/%secure')
    {
        $configData = Mage::getModel('core/config_data')->getCollection();
        $configData->addPathFilter('web/%secure');

        $tree = array();
        foreach ($configData as $datum) {
            $tree[$datum->getPath()] = $this->_bestScope(@$tree[$datum->getPath()], $datum);
        }
        foreach ($tree as &$datum) {
            $datum = $datum->getValue();
        }
        return $tree;
    }

    private function _bestScope(Mage_Core_Model_Config_Data $old, Mage_Core_Model_Config_Data $new)
    {
        $store = Mage::app()->getStore();
        if ($store->getWebsite() === false) {
            $store->load($store->getId());
        }

        if (! $old) {
            // must have at least one entry
            // only chance for global item to apply
            return $new;
        }
        if (($new->getScope() == 'stores') && ($new->getScopeId() == $store->getId())) {
            // store scope beats all other scopes
            return $new;
        }
        if (($new->getScope() == 'websites') && ($new->getScopeId() == $store->getWebsite()->getId())) {
            if ($old->getScope() != 'stores') {
                // website is only superseded by stores
                return $new;
            }
        }
        return $old;
    }
}
