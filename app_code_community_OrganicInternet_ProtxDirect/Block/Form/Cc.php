<?php

class OrganicInternet_ProtxDirect_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('organicinternet_protxdirect/cc.phtml');
    }

    protected function _getConfig()
    {
        return Mage::getSingleton('organicinternet_protxdirect/config');
    }

    public function getCcStartYears()
    {
        $years = $this->getData('cc_start_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getStartYears();
            $years = array(0=>$this->__('Year')) + $years;
            $this->setData('cc_start_years', $years);
        }
        return $years;
    }

    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cc_protxdirect/types');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

}