<?php

class OrganicInternet_ProtxDirect_Model_Source_Cctype extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes()
    {
        return array('VISA', 'MC', 'DELTA', 'SOLO', 'MAESTRO', 'AMEX', 'UKE');
    }
}