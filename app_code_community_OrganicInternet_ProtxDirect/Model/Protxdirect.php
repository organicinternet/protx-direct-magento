<?php

class DDDSecureRequiredException extends Exception { }

class OrganicInternet_ProtxDirect_Model_ProtxDirect extends Mage_Payment_Model_Method_Cc
{
    protected $_code          = 'organicinternet_protxdirect';
    protected $_formBlockType = 'organicinternet_protxdirect/form_cc';
    protected $_infoBlockType = 'organicinternet_protxdirect/info';

    protected $_isGateway               = true;
    protected $_canAuthorize            = false;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc 				= false;


    const RESPONSE_DELIM_CHAR = "\r\n";

    const RESPONSE_CODE_APPROVED  = 'OK';
    const RESPONSE_CODE_REJECTED  = 'REJECTED';
    const RESPONSE_CODE_INVALID   = 'INVALID';
    const RESPONSE_CODE_ERROR     = 'ERROR';
    const RESPONSE_CODE_NOTAUTHED = 'NOTAUTHED';
    const RESPONSE_CODE_3DAUTH    = '3DAUTH';
    const RESPONSE_CODE_MALFORMED = 'MALFORMED';

    public function assignData($data)
    {

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        parent::assignData($data);
        $info = $this->getInfoInstance();

        if ($data->getCcIssue()) {
            $info->setCcSsIssue($data->getCcIssue());
        }
        if ($data->getCcStartMonth() && $data->getCcStartYear()) {
            $info->setCcSsStartMonth($data->getCcStartMonth())
                 ->setCcSsStartYear($data->getCcStartYear());
        }
        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $error = false;
        $payment->setAmount($amount);

        if ($PaRes = $this->getCheckout()->getPaRes()) {
            // 3D Secure auth. done
            $request= $this->_buildRequest3D($PaRes, $this->getCheckout()->getMD());
            $result = $this->_postRequest($request, true);

        } else {
            $request = $this->_buildRequest($payment);
            $result  = $this->_postRequest($request);
        }

        switch($result->getStatus())
        {
            case self::RESPONSE_CODE_APPROVED:
                $payment->setStatus(self::STATUS_APPROVED)
                        ->setCcTransId($result->getVPSTxId())
                        ->setLastTransId($result->getVPSTxId())
                        ->setCcAvsStatus($result->getAddressResult())
                        ->setCcPostcodeResult($result->getPostCodeResult())
                        ->setCcCv2Result($result->getCV2Result())
                        ->setCc3dSecureStatus($result->get3DSecureStatus());
    
                break;
            case self::RESPONSE_CODE_NOTAUTHED:
                $error = Mage::helper('organicinternet_protxdirect')
                            ->__('Your card could not be authorised: '.$result->getResponseStatusDetail());
                break;
            case self::RESPONSE_CODE_REJECTED:
                $error = Mage::helper('organicinternet_protxdirect')
                            ->__('Your card was rejected: '.$result->getResponseStatusDetail());
                break;
            case self::RESPONSE_CODE_3DAUTH:
                // 3D Secure Data
                Mage::getSingleton('checkout/session')
                    ->setMD($result->getMD())
                    ->setACSURL($result->getACSURL())
                    ->setPaReq($result->getPAReq())
                    ->setRedirectUrl($this->getOrderPlaceRedirectUrl());
Mage::log('BOOYAA');
                throw new DDDSecureRequiredException('3D Secure Authentication Required');
                break;
            default:
                $error = Mage::helper('organicinternet_protxdirect')->__('Unknown error occurred while capturing payment');
        }

        Mage::getSingleton('checkout/session')->unsMD()
                                              ->unsPaRes()
                                              ->unsPaReq()
                                              ->unsACSURL();

        if ($error !== false) {
            Mage::throwException($error);
        }
        return $this;
    }

    public function getProtxUrl ()
    {
        switch ($this->getConfigData('mode')) {
            case OrganicInternet_ProtxDirect_Model_Config::MODE_LIVE:
                $url = 'https://ukvps.protx.com/vspgateway/service/vspdirect-register.vsp';
                break;
            case OrganicInternet_ProtxDirect_Model_Config::MODE_TEST:
                $url = 'https://ukvpstest.protx.com/vspgateway/service/vspdirect-register.vsp';
                break;
            default: // simulator mode
                $url = 'https://ukvpstest.protx.com/VSPSimulator/VSPDirectGateway.asp';
                break;
        }
        return $url;
    }

    public function get3DSecureCallbackUrl ()
    {
        switch ($this->getConfigData('mode')) {
    
            case OrganicInternet_ProtxDirect_Model_Config::MODE_LIVE:
                $url = 'https://ukvps.protx.com/vspgateway/service/direct3dcallback.vsp';
                break;
            case OrganicInternet_ProtxDirect_Model_Config::MODE_TEST:
                $url = 'https://ukvpstest.protx.com/vspgateway/service/direct3dcallback.vsp';
                break;
            default: // simulator mode
                $url = 'https://ukvpstest.protx.com/VSPSimulator/VSPDirectCallback.asp';
                break;
        }
        return $url;
    }

    public function getOrderPlaceRedirectUrl()
    {
        // redirect if 3D secure required, otherwise continue
        return Mage::getSingleton('checkout/session')->getMD() ? Mage::getUrl('oiprotxdirect/direct/capture') : '';
    }

    protected function _buildRequest(Varien_Object $payment)
    {
        $order = $payment->getOrder();

        $request = Mage::getModel('organicinternet_protxdirect/request')
            ->setVPSProtocol(2.22)
            ->setTxType($this->getConfigData('protx_payment_action'))
            ->setVendor($this->getConfigData('vendor_name'))
            ->setVendorTxCode($this->getVendorTxCode($order));

        $request->setCurrency($order->getBaseCurrencyCode());
        $request->setDescription($this->getConfigData('description'));

        if($payment->getAmount()){
            $request->setAmount($payment->getAmount(),2);
        }


        if (!empty($order)) {

            $billing = $order->getBillingAddress();
            if (!empty($billing)) {
                $request->setBillingAddress($billing->getStreet(1) . ' ' . $billing->getCity() . ' ' .
                $billing->getRegion() . ' ' . $billing->getCountry()
                            )
                    ->setBillingPostCode($billing->getPostcode())
                    ->setCustomerName($billing->getFirstname().' '.$billing->getLastname())
                    ->setContactNumber($billing->getTelephone())
                    ->setContactFax($billing->getFax())
                    ->setCustomerEMail($billing->getEmail());
            }

            $shipping = $order->getShippingAddress();
            if (!empty($shipping)) {
                $request->setDeliveryAddress($shipping->getStreet(1) . ' ' . $shipping->getCity() . ' ' .
                $shipping->getRegion() . ' ' . $shipping->getCountry()
                            )
                    ->setDeliveryPostCode($shipping->getPostcode());
            }

            $request->setBasket($this->getFormattedBasket($order));

        }

        if($payment->getCcNumber()){
            $request->setCardNumber($payment->getCcNumber())
                    ->setExpiryDate(sprintf('%02d%02d', $payment->getCcExpMonth(), substr($payment->getCcExpYear(), strlen($payment->getCcExpYear()) - 2)))
                    ->setCardType($payment->getCcType())
                    ->setCV2($payment->getCcCid())
                    ->setCardHolder($payment->getCcOwner());

                if ($payment->getCcSsIssue()) {
                    $request->setIssueNumber($payment->getCcSsIssue());
                }
                if ($payment->getCcSsStartMonth() && $payment->getCcSsStartYear()) {
                    $request->setStartDate(sprintf('%02d%02d', $payment->getCcSsStartMonth(), substr($payment->getCcSsStartYear(), strlen($payment->getCcSsStartYear()) - 2)));
                }
        }

        $request->setApplyAVSCV2($this->getConfigData('avscv2'));
        $request->setApply3DSecure($this->getConfigData('dddsecure'));
        return $request;
    }

    protected function _buildRequest3D($PARes, $MD)
    {

        $request = Mage::getModel('organicinternet_protxdirect/request')
            ->setMD($MD)
            ->setPARes($PARes);

        return $request;
    }

    public function OtherCcType($type)
    {
        return true;
    }

    private function cleanString($text)
    {
            $pattern = '|[^a-zA-Z0-9\-\._]+|';
            $text = preg_replace($pattern, '', $text);

            return $text;
    }

    protected function _postRequest(Varien_Object $request, $callback3D = false)
    {
    
        $result = Mage::getModel('organicinternet_protxdirect/result');

        $client = new Varien_Http_Client();

        if ($callback3D) {
            $client->setUri($this->get3DSecureCallbackUrl());
        } else {
            $client->setUri($this->getProtxUrl());
        }

        $client->setConfig(array(
            'maxredirects'=>0,
            'timeout'=>30,
            //'ssltransport' => 'tcp',
        ));
        $client->setParameterPost($request->getData());
        $client->setMethod(Zend_Http_Client::POST);

        try {
            $response = $client->request();
        } catch (Exception $e) {
            $result->setResponseCode(-1)
                ->setResponseReasonCode($e->getCode())
                ->setResponseReasonText($e->getMessage());

             Mage::throwException(
                Mage::helper('organicinternet_protxdirect')->__('Gateway request error: %s', $e->getMessage())
            );

        }

        $responseBody = $response->getBody();
    
        $t = explode(self::RESPONSE_DELIM_CHAR, $responseBody);
        $r = array();
        while (list($key, $item) = each($t)) {
            $temp = split("=",$item);
            $val = '';
            if (count($temp)>1) {
                $val = $temp[1];
                $keyProtx = $temp[0];
            }
            $r[$keyProtx] = $val;
            $setter = "set{$keyProtx}";
            $result->$setter($val);
        }
        //$result->setData($r);

       if ($r['Status']==self::RESPONSE_CODE_INVALID || $r['Status']==self::RESPONSE_CODE_MALFORMED || $r['Status']==self::RESPONSE_CODE_ERROR) {
           Mage::throwException(
           Mage::helper('organicinternet_protxdirect')->__('Error in payment. Protx says: %s', $r['StatusDetail']));
        }

        return $result;
    }

    protected function getFormattedBasket($order)
    {
        $items = $order->getAllItems();
        $resultParts = array();
        $totalLines = 0;

        if ($items) {
            foreach($items as $item) {
                $quantity = $item->getQtyOrdered();

                $cost = sprintf('%.2f', $item->getBasePrice() - $item->getBaseDiscountAmount());
                $tax = sprintf('%.2f', $item->getBaseTaxAmount());
                $costPlusTax = sprintf('%.2f', $cost + $tax/$quantity);

                $totalCostPlusTax = sprintf('%.2f', $quantity * $cost + $tax);

                $resultParts[] = $this->stripColons($item->getName());
                $resultParts[] = $quantity;
                $resultParts[] = $cost;
                $resultParts[] = $tax;
                $resultParts[] = $costPlusTax;
                $resultParts[] = $totalCostPlusTax;
            }
            $totalLines = count($items);
       }

       // add delivery
       $shipping = $order->getBaseShippingAmount();
       if ((int)$shipping > 0) {
           $totalLines++;
           $resultParts = array_merge($resultParts, array('Shipping','','','','',sprintf('%.2f', $shipping)));
       }

       $result = $totalLines . ':' . implode(':', $resultParts);
       return $result;
    }

    protected function stripColons($text)
    {
        return str_replace(':', ';', $text);
    }

    function getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    public function validate()
    {
	
        /*
        * calling parent validate function
        */
        //parent::validate();

        $info = $this->getInfoInstance();
        $errorMsg = false;
        $availableTypes = explode(',',$this->getConfigData('cctypes'));

        $ccNumber = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';

        if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorCode = "{$this->_code}_expiration,{$this->_code}_expiration_yr";
            $errorMsg = $this->_getHelper()->__('Incorrect card expiration date');
        }

		if (($info->getCcStartYear() || $info->getCcStartMonth()) && !$this->_validateStartDate($info->getCcStartYear(), $info->getCcStartMonth())) {
            $errorCode = "{$this->_code}_start,{$this->_code}_start_yr";
            $errorMsg = $this->_getHelper()->__('Incorrect card start date');
        }

        if ($info->getCcIssue()) {
	        if (!preg_match('/\d+/', $info->getCcIssue())) {
				$errorCode = "{$this->_code}_cc_issue";
	            $errorMsg = $this->_getHelper()->__('Incorrect card issue number');
		    }
	    }

        if (in_array($info->getCcType(), $availableTypes)){
            if ($this->validateCcNum($ccNumber)/*
                // Other credit card type number validation
                || ($this->OtherCcType($info->getCcType()) && $this->validateCcNumOther($ccNumber))*/) { /*

                $ccType = 'OT';
                $ccTypeRegExpList = array(
                    'VI' => '/^4[0-9]{12}([0-9]{3})?$/', // Visa
                    'MC' => '/^5[1-5][0-9]{14}$/',       // Master Card
                    'AE' => '/^3[47][0-9]{13}$/',        // American Express
                    'DI' => '/^6011[0-9]{12}$/'          // Discovery
                );

                foreach ($ccTypeRegExpList as $ccTypeMatch=>$ccTypeRegExp) {
                    if (preg_match($ccTypeRegExp, $ccNumber)) {
                        $ccType = $ccTypeMatch;
                        break;
                    }
                }

                if (!$this->OtherCcType($info->getCcType()) && $ccType!=$info->getCcType()) {
                    $errorCode = "{$this->_code}_cc_type,{$this->_code}_cc_number";
                    $errorMsg = $this->_getHelper()->__('Card number mismatch with credit card type');
                }*/
            }
            else {
                $errorCode = "{$this->_code}_cc_number";
                $errorMsg = $this->_getHelper()->__('Invalid Credit Card Number');
            }

        }
        else {
            $errorCode = "{$this->_code}_cc_type";
            $errorMsg = $this->_getHelper()->__('Credit card type is not allowed for this payment method');
        }

        if($errorMsg){
            Mage::throwException($errorMsg);
            //throw Mage::exception('Mage_Payment', $errorMsg, $errorCode);
        }

        return $this;
    }

    protected function _validateStartDate($startYear, $startMonth)
    {
        $date = Mage::app()->getLocale()->date();
        if (($date->compareYear($startYear)<0) || ($date->compareYear($startYear) == 0 && $date->compareMonth($startMonth)<0)) {
            return false;
        }
        return true;
    }
    
    protected function getVendorTxCode($order)
    {
        return $order->getRealOrderId();
    }

    public function getCcTypes()
    {
        foreach (Mage::getSingleton('payment/config')->getCcTypes() as $code => $name) {
            $ccTypes[$code] = $name;
        }
        return $ccTypes;
    }

}