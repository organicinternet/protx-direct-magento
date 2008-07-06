<?php

class OrganicInternet_ProtxDirect_Block_Info extends Mage_Payment_Block_Info_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('organicinternet_protxdirect/info.phtml');
    }

    protected function getProtxDirect()
    {
        return Mage::getSingleton('organicinternet_protxdirect/protxdirect');
    }

     /**
     * Retrieve credit card type name
     *
     * @return string
     */
    public function getCcTypeName()
    {
        $types = $this->getProtxDirect()->getCcTypes();
        if (isset($types[$this->getInfo()->getCcType()])) {
            return $types[$this->getInfo()->getCcType()];
        }
        return $this->getInfo()->getCcType();
    }

    /**
     * Retrieve CC start month for switch/solo card
     *
     * @return string
     */
    public function getCcStartMonth()
    {
        $month = $this->getInfo()->getCcStartMonth();
        if ($month<10) {
            $month = '0'.$month;
        }
        return $month;
    }
    
    public function toPdf()
    {
        $this->setTemplate('organicinternet_protxdirect/pdf/info.phtml');
        return $this->toHtml();
    }
}