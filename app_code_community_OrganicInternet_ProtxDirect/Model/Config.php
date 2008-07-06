<?php

class OrganicInternet_ProtxDirect_Model_Config extends Varien_Object
{
    
    const MODE_SIMULATOR    = 'SIMULATOR';
    const MODE_TEST         = 'TEST';
    const MODE_LIVE         = 'LIVE';
    
    const AVSCV2_USE_WITH_RULES         = 0;
    const AVSCV2_FORCE_WITH_RULES       = 1;
    const AVSCV2_FORCE_OFF              = 2;
    const AVSCV2_FORCE_WITHOUT_RULES    = 3;
    
    const DDDSECURE_CHECK_WITH_RULES    = 0;
    const DDDSECURE_FORCE_WITH_RULES    = 1;
    const DDDSECURE_DISABLE             = 2;
    const DDDSECURE_FORCE_WITHOUT_RULES = 3;
    
    const ACCOUNT_ECOMMERCE             = 'E';
    const ACCOUNT_CONTINUOUS            = 'C';
    const ACCOUNT_MAIL_ORDER            = 'M';

    const PAYMENT_TYPE_PAYMENT      = 'PAYMENT';
    const PAYMENT_TYPE_DEFERRED     = 'DEFERRED';
    const PAYMENT_TYPE_AUTHENTICATE = 'AUTHENTICATE';
    const PAYMENT_TYPE_AUTHORISE    = 'AUTHORISE';

    /**
     *  Return config var
     *
     *  @param    string Var key
     *  @param    string Default value for non-existing key
     *  @return	  mixed
     */
    public function getConfigData($key, $default=false)
    {
        if (!$this->hasData($key)) {
             $value = Mage::getStoreConfig('payment/protx_standard/'.$key);
             if (is_null($value) || false===$value) {
                 $value = $default;
             }
            $this->setData($key, $value);
        }
        return $this->getData($key);
    }

    /**
     *  Return Protocol version
     *
     *  @return	  string Protocol version
     */
    public function getVersion ()
    {
        return '2.22';
    }

    /**
     *  Return Store description sent to Protx
     *
     *  @return	  string Description
     */
    public function getDescription ()
    {
        return $this->getConfigData('description');
    }

    /**
     *  Return Protx registered merchant account name
     *
     *  @return	  string Merchant account name
     */
    public function getVendorName ()
    {
        return $this->getConfigData('vendor_name');
    }

    /**
     *  Return Protx merchant password
     *
     *  @return	  string Merchant password
     */
    public function getVendorPassword ()
    {
        return $this->getConfigData('vendor_password');
    }

    /**
     *  Return preferred payment type (see SELF::PAYMENT_TYPE_* constants)
     *
     *  @return	  string payment type
     */
    public function getPaymentType ()
    {
        return $this->getConfigData('payment_action');
    }

    /**
     *  Return working mode (see SELF::MODE_* constants)
     *
     *  @return	  string Working mode
     */
    public function getMode ()
    {
        return $this->getConfigData('mode');
    }

    /**
     *  Return new order status
     *
     *  @return	  string New order status
     */
    public function getNewOrderStatus ()
    {
        return $this->getConfigData('order_status');
    }
    
    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getCcTypesProtx()
    {
        $types = array();
        foreach (Mage::getConfig()->getNode('global/payment/cc_protx/types')->asArray() as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }

    public function getStartYears()
    {
        $years = array();
        $first = date("Y");

        for ($index=5; $index>=0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Retrieve active system carriers
     *
     * @param   mixed $store
     * @return  array
     */
    public function getActiveMethods($store=null)
    {
        $methods = array();
        $config = Mage::getStoreConfig('payment', $store);
        foreach ($config as $code => $methodConfig) {
            if (Mage::getStoreConfigFlag('payment/'.$code.'/active', $store)) {
                $methods[$code] = $this->_getMethod($code, $methodConfig);
            }
        }
        return $methods;
    }

    protected function _getMethod($code, $config, $store=null)
    {
        if (isset(self::$_methods[$code])) {
            return self::$_methods[$code];
        }
        $modelName = $config['model'];
        $method = Mage::getModel($modelName);
        $method->setId($code)->setStore($store);
        self::$_methods[$code] = $method;
        return self::$_methods[$code];
    }

    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getCcTypes()
    {
        $_types = Mage::getConfig()->getNode('global/payment/cc_protxdirect/types')->asArray();

        uasort($_types, array('Mage_Payment_Model_Config', 'compareCcTypes'));

        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }

    /**
     * Retrieve list of months translation
     *
     * @return array
     */
    public function getMonths()
    {
        $data = Mage::app()->getLocale()->getLocale()->getTranslationList('month');
        foreach ($data as $key => $value) {
            $monthNum = ($key < 10) ? '0'.$key : $key;
            $data[$key] = $monthNum . ' - ' . $value;
        }
        return $data;
    }

    /**
     * Retrieve array of available years
     *
     * @return array
     */
    public function getYears()
    {
        $years = array();
        $first = date("Y");

        for ($index=0; $index<10; $index++) {
            $year = $first + $index;
            $years[$year] = $year;
        }
        return $years;
    }

    /**
     * Statis Method for compare sort order of CC Types
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    static function compareCcTypes($a, $b)
    {
        if (!isset($a['order'])) {
            $a['order'] = 0;
        }

        if (!isset($b['order'])) {
            $b['order'] = 0;
        }

        if ($a['order'] == $b['order']) {
            return 0;
        } else if ($a['order'] > $b['order']) {
            return 1;
        } else {
            return -1;
        }

    }


}
