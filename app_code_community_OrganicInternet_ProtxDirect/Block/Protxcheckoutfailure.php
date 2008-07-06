<?php

class OrganicInternet_ProtxDirect_Block_ProtxCheckoutFailure extends Mage_Core_Block_Template
{
    public function getFailureMessage() {
	    return Mage::getSingleton('checkout/session')->getProtxFailureMessage();
    }
}
