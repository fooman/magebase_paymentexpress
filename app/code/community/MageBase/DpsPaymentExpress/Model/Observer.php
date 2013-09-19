<?php
class MageBase_DpsPaymentExpress_Model_Observer
{
    public function allowDpsNotifications($observer)
    {
        $controller = $observer->getEvent()->getController();
        $result = $observer->getEvent()->getResult();
        if ($controller instanceof MageBase_DpsPaymentExpress_PxpayController) {
            $result->setShouldProceed(false);
        }
    }

    public function addMageOneFourConfig($observer)
    {
        if (version_compare(Mage::getVersion(), '1.5.0.0', '<')) {
            $file = Mage::getModuleDir('etc', 'MageBase_DpsPaymentExpress') . DS . 'config-1.4.xml';
            $config = Mage::getConfig();
            $prototype = new Mage_Core_Model_Config_Base();
            $prototype->loadFile($file);
            $config->extend($prototype);
            $config->saveCache();
        }
    }
}



