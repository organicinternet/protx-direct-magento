<?php

require_once 'Mage/Checkout/controllers/MultishippingController.php';

class OrganicInternet_ProtxDirect_MultishippingController extends Mage_Checkout_MultishippingController
{

    public function overviewPostAction()
    {
        try {
            $payment = $this->getRequest()->getPost('payment');
            $paymentInstance = $this->_getCheckout()->getQuote()->getPayment();
            if (isset($payment['cc_number'])) {
                $paymentInstance->setCcNumber($payment['cc_number']);
            }
            if (isset($payment['cc_cid'])) {
                $paymentInstance->setCcCid($payment['cc_cid']);
            }
            $this->_getCheckout()->createOrders();
            $this->_getState()->setActiveStep(
                Mage_Checkout_Model_Type_Multishipping_State::STEP_SUCCESS
            );
            $this->_getCheckout()->getCheckoutSession()->clear();
            $this->_getCheckout()->getCheckoutSession()->setDisplaySuccess(true);
            $this->_redirect('checkout/multishipping/success');
        }
        catch (DDDSecureRequiredException $e) {
	        $this->_getCheckout()->getCheckoutSession()->setCheckoutType('multishipping');
	        header("Location: ".$this->_getCheckout()->getCheckoutSession()->getRedirectUrl());
	        exit;
        } 
        catch (Mage_Core_Exception $e){
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
            $this->_redirect('checkout/multishipping/billing');
        }
        catch (Exception $e){
            Mage::getSingleton('checkout/session')->addError('Order place error.');
            $this->_redirect('checkout/multishipping/billing');
        }
    }

}