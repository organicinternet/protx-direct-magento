<?php

class OrganicInternet_ProtxDirect_Model_Source_AccountAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => OrganicInternet_ProtxDirect_Model_Config::ACCOUNT_ECOMMERCE, 'label' => Mage::helper('organicinternet_protxdirect')->__('E-Commerce Account')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::ACCOUNT_CONTINUOUS, 'label' => Mage::helper('organicinternet_protxdirect')->__('Continuous Authority Account')),
            array('value' => OrganicInternet_ProtxDirect_Model_Config::ACCOUNT_MAIL_ORDER, 'label' => Mage::helper('organicinternet_protxdirect')->__('Mail Order Account')),
        );
    }
}