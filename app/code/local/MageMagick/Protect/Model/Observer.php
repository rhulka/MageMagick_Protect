<?php
/**
 * MageMagick Protect
 *
 * @category    MageMagick
 * @package     MageMagick_Protect
 * @copyright   Copyright (c) 2012 MageMagick
 */
class MageMagick_Protect_Model_Observer
{
    protected static $addSplashScreen = false;

    protected static $handleId = 'page';


    public function __construct()
    {
        // STUB
    }

    /**
     * MageMagick_Protect_Model_Observer:checkAccess
     *
     *  This method checks if user have permissions for browse store,
     *  if have no permissions then  will be redirected on login page.
     *
     *  NOTE: I think would be better overwrite front controller,
     *  because observer not intended in general for implement tasks like this,
     *  but this was fastest way
     *
     * @param Varien_Event_Observer $observer
     * @return mixed
     */
    public function checkAccess(Varien_Event_Observer $observer)
    {
        if (0 === (int)Mage::getStoreConfig('protect/protect/enabled')) {
            return $this;
        }

        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();

        if (false != $isLoggedIn) {
            return $this;
        }

        $request        = Mage::app()->getRequest();
        $response       = $observer->getControllerAction()->getResponse();
        $moduleName     = $request->getModuleName();
        $controllerName = $request->getControllerName();
        $action         = $request->getActionName();

        if ($moduleName == 'admin') {
            return $this;
        }

        if ($moduleName != 'customer' && $moduleName != 'cms') {
            $this->_hardRedirect($request, $response, Mage::getUrl('/'));
        }

        self::$addSplashScreen = true;

        if ($moduleName == 'cms' && $controllerName == 'index') {
            self::$handleId = 'default';
            return $this;
        }

        if ($moduleName == 'customer') {
            self::$handleId = $action;
            return $this;
        }

        return $this;
    }

    /**
     * MageMagick_Protect_Model_Observer:updateLayout
     *
     * This method removes useless blocks and links in layout if user not logged in.
     *
     * @param Varien_Event_Observer $observer
     * @return MageMagick_Protect_Model_Observer
     */
    public function updateLayout(Varien_Event_Observer $observer)
    {
        if (0 === (int)Mage::getStoreConfig('protect/protect/enabled')) {
            return $this;
        }

        $isLoggedIn = Mage::getSingleton('customer/session')->isLoggedIn();
        if (!self::$addSplashScreen || false != $isLoggedIn) {
            return $this;
        }

        $observer->getEvent()
                 ->getLayout()
                 ->getUpdate()
                 ->addHandle('mmprotect_splash_' . self::$handleId);

        return $this;
    }

    /**
     * MageMagick_Protect_Model_Observer:_hardRedirect
     *
     * HTTP redirect to another page.
     *
     * @important method halts script execution
     *
     * @param $request
     * @param response
     * @param $url
     * @return void
     */
    protected function _hardRedirect($request, $response, $url)
    {
        $response->setRedirect($url, 302);
        $response->sendHeaders();
        $request->setDispatched(true);

        exit; // TODO: halting script execution, bad idea doing this in observer method...
    }
}
