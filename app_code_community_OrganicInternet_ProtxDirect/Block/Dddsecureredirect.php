<?php

class OrganicInternet_ProtxDirect_Block_DddSecureRedirect extends Mage_Core_Block_Template
{
    public function getRedirectUrl() {
	    return Mage::getUrl('oiprotxdirect/direct/redirect');
    }
}
