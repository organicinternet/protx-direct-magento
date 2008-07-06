<?php

class OrganicInternet_ProtxDirect_DirectController extends Mage_Core_Controller_Front_Action
{
 
    public function getProtxDirect()
    {
        return Mage::getSingleton('oiprotxdirect/direct/redirect');
    }

    public function getConfig()
    {
        return $this->getProtxDirect()->getConfig();
    }

    public function getDebug ()
    {
        return $this->getProtxDirect()->getDebug();
    }

    public function captureAction()
    {
	
	    // we've been redirected here because we need to do 3DS. Show an iframe with the Bank's authentication page.
	    $session = Mage::getSingleton('checkout/session');

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $order->addStatusToHistory(
            $order->getStatus(),
            Mage::helper('organicinternet_protxdirect')->__('Customer was forwarded to Protx Direct capture page')
        );

	    
	    $this->loadLayout();
        $this->renderLayout();
	    
    }

	public function redirectAction() {
		$session = Mage::getSingleton('checkout/session');
		$termUrl = Mage::getUrl('oiprotxdirect/direct/dddcallback');
		
		$redirect = <<<EOS
		 <html>
		  <head>
		   <title>3D Secure Authentication</title>
		   <script type="text/javascript">
		     function submit3dSecureForm() { document.form.submit(); }
		   </script>
		  </head>
		  <body onload="submit3dSecureForm()">
		   <form name="form" action="{$session->getACSURL()}" method="post">
		    <input type="hidden" name="PaReq" value="{$session->getPaReq()}" />
		    <input type="hidden" name="TermUrl" value="{$termUrl}" />
		    <input type="hidden" name="MD" value="{$session->getMD()}" />
		    <noscript>
		     <center><p>Please click button below to authenticate your card</p><p><input type="submit" value="Go" /></p></center>
		    </noscript>
		   </form>
		  </body>
		 </html>
EOS;
		$this->getResponse()->setBody($redirect);
	}

    public function dddcallbackAction()
    {
	    // We're here because the customer has completed 3DS, and the bank have redirected to here.
	    // We now need to give the results to Protx and let them decide if we've billed or not.
	    $session = Mage::getSingleton('checkout/session');

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $order->addStatusToHistory(
            $order->getStatus(),
            Mage::helper('organicinternet_protxdirect')->__('Customer returned to 3DS callback page')
        );

	    try {
		    $protxDirect = $this->getQuote()->getPayment()->getMethodInstance();
		    $this->getCheckout()->setPaRes($this->getRequest()->getPost('PaRes'));
		    if ($this->getCheckout()->getCheckoutType() == 'multishipping') {
				//Mage::throwException(var_export($this->getCheckout()->getData(), true));
			    $next_url = Mage::getUrl('checkout/multishipping/overviewPost');
			} else {
			    $this->getOnepage()->saveOrder();
			    $next_url = Mage::getUrl('checkout/onepage/success');
			    $this->getCheckout()->unsPaReq();
			    $this->getCheckout()->unsPaRes();
			    $this->getCheckout()->unsMD();
			    $this->getCheckout()->getACSURL();
			}
		} catch (Exception $e) {
			Mage::log("capture3D failed with ".$e);
			
			$next_url = Mage::getUrl('oiprotxdirect/direct/failure');
			$this->getCheckout()->setProtxFailureMessage($e->getMessage());
		}
		
		$success_redirect = <<<EOS
		<html>
		 <head><title>Order Result</title></head>
		 <body>
		  <script type="text/javascript">
		    window.top.location = '{$next_url}';
		  </script>
		 </body>
		</html>
EOS;
		$this->getResponse()->setBody($success_redirect);
    }

    public function failureAction() {
	    $this->loadLayout();
        $this->renderLayout();
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }


}