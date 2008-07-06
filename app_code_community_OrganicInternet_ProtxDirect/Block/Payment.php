<?php

class OrganicInternet_ProtxDirect_Adminhtml_Block_Sales_Order_Payment extends Mage_Adminhtml_Block_Sales_Order_Payment
{

    public function setPayment($payment)
    {
        parent::setPayment($payment);
        if ($payment->getMethod() == 'organicinternet_protxdirect') {
            $paymentInfoBlock->setTemplate('organicinternet_protxdirect/info.phtml');
        }
        return $this;
    }

    protected function _toHtml()
    {
        return $this->getChildHtml('info');
    }

}