<?php

class OrganicInternet_ProtxDirect_Model_Source_3dsecureAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => OrganicInternet_ProtxDirect_Model_Config::DDDSECURE_CHECK_WITH_RULES, 'label' => Mage::helper('organicinternet_protxdirect')->__('Check 3D-Secure if possible and apply rules')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::DDDSECURE_FORCE_WITH_RULES, 'label' => Mage::helper('organicinternet_protxdirect')->__('Force 3D-Secure and use rules')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::DDDSECURE_DISABLE, 'label' => Mage::helper('organicinternet_protxdirect')->__('Disable 3D-Secure')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::DDDSECURE_FORCE_WITHOUT_RULES, 'label' => Mage::helper('organicinternet_protxdirect')->__('Force AVS/CV2 checks but don\'t apply rules')),
        );
    }
}