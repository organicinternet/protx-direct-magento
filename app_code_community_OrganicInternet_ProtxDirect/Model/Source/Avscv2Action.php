<?php

class OrganicInternet_ProtxDirect_Model_Source_Avscv2Action
{
    public function toOptionArray()
    {
        return array(
            array('value' => OrganicInternet_ProtxDirect_Model_Config::AVSCV2_USE_WITH_RULES, 'label' => Mage::helper('organicinternet_protxdirect')->__('Check AVS/CV2 if enabled and use rules')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::AVSCV2_FORCE_WITH_RULES, 'label' => Mage::helper('organicinternet_protxdirect')->__('Force AVS/CV2 checks and use rules')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::AVSCV2_FORCE_OFF, 'label' => Mage::helper('organicinternet_protxdirect')->__('Disable AVS/CV2 checks')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::AVSCV2_FORCE_WITHOUT_RULES, 'label' => Mage::helper('organicinternet_protxdirect')->__('Force AVS/CV2 checks but don\'t use rules')),
        );
    }
}