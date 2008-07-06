<?php

require_once 'Mage/Checkout/controllers/OnepageController.php';

class OrganicInternet_ProtxDirect_OnepageController extends Mage_Checkout_OnepageController
{
    public function saveOrderAction()
    {
        $this->_expireAjax();

       try {
            if ($data = $this->getRequest()->getPost('payment', false)) {
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }
            $this->getOnepage()->saveOrder();
            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result['success'] = true;
            $result['error']   = false;
        }
	    catch (DDDSecureRequiredException $e) {
		    // 3D Secure authentication required
		    //$this->getOnepage()->getCheckout()->setCheckoutType('onepage');
            $result['success'] = false;
            $result['error']   = false;
		    $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
        }
        catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $e->getMessage();
        }
        catch (Exception $e) {
            Mage::logException($e);
            $result['success']  = false;
            $result['error']    = true;
            $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
        }

        /**
         * when there is redirect to third party, we don't want to save order yet.
         * we will save the order in return action.
         */
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }

        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
}