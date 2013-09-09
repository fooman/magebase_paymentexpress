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

    public function setOrderPaymentPreviewState($observer)
    {
        $order = $observer->getPayment()->getOrder();
        if ($order->getPaymentPreviewState()) {
            $state = $order->getPaymentPreviewState();
            $order->setStatus($state);
        }
    }
}