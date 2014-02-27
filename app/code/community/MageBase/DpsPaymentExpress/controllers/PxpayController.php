<?php
/**
 * MageBase DPS Payment Express
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    MageBase
 * @package     MageBase_DpsPaymentExpress
 * @author      Kristof Ringleff
 * @copyright   Copyright (c) 2010 MageBase (http://www.magebase.com)
 * @copyright   Copyright (c) 2010 Fooman Ltd (http://www.fooman.co.nz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MageBase_DpsPaymentExpress_PxpayController extends Mage_Core_Controller_Front_Action
{

    public function successAction()
    {
        Mage::log(
            'MageBaseDps successAction userid ' . $this->getRequest()->getParam('userid'),
            null,
            MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
        );
        Mage::log(
            'MageBaseDps successAction result ' . $this->getRequest()->getParam('result'),
            null,
            MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
        );
        if ($this->_validateUserId($this->getRequest()->getParam('userid'))) {

            /* successAction is called twice
             * once by DPS's FailProofNotification
             * and also by the customer when returning
             * DPS has no session
             * only the DPS response is processed to prevent double handling / order locking
             * This behaviour can be changed with the test locally option
             */
            $session = Mage::getSingleton('checkout/session');
            //session exists = user with browser
            $testLocally = Mage::getStoreConfig(
                'payment/' . Mage::getModel('magebasedps/method_pxpay')->getCode() . '/test_locally'
            );
            if ($session->getLastOrderId()) {
                Mage::log(
                    $session->getLastRealOrderId() . ' MageBaseDps User returned to Success Url',
                    null,
                    MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
                );

                if ($testLocally) {
                    $resultXml = $this->_processSuccessResponse($this->getRequest()->getParam('result'));
                } else {
                    $resultXml = $this->_getRealResponse($this->getRequest()->getParam('result'));
                }

                //we have a response from DPS
                if ($resultXml) {
                    if ((int)$resultXml->Success == 1) {
                        $session->setLastQuoteId((int)$resultXml->TxnData2)
                            ->setLastOrderId(
                                Mage::getModel('sales/order')->loadByIncrementId((string)$resultXml->MerchantReference)
                                    ->getId()
                            )
                            ->setLastRealOrderId((string)$resultXml->MerchantReference)
                            ->setLastSuccessQuoteId((int)$resultXml->TxnData2);
                        $this->_redirect('checkout/onepage/success', array('_secure' => true));
                    } else {
                        if ((int)$resultXml->TxnData2) {
                            $session->setLastQuoteId((int)$resultXml->TxnData2);
                        }
                        if ((string)$resultXml->MerchantReference) {
                            $session->setLastOrderId(
                                Mage::getModel('sales/order')->loadByIncrementId(
                                    (string)$resultXml->MerchantReference
                                )
                                    ->getId()
                            )
                                ->setLastRealOrderId((string)$resultXml->MerchantReference);
                        }
                        $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                    }
                    //we didn't get a successful response
                } else {
                    //we don't have a proper response - fail but we don't know why
                    Mage::log(
                        $session->getLastRealOrderId() . ' MageBaseDps User returned to Success Url but we were unable to
                        retrieve a positive response from DPS',
                        null,
                        MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
                    );
                    // display success problem template, which will reload this URL
                    // magebase/dps/pxpay/successproblem.phtml
                    $this->loadLayout();
                    $this->renderLayout();
                }
                //session doesn't exist = DPS notification
            } else {
                if ($testLocally) {
                    return;
                }

                try {
                    $result = $this->getRequest()->getParam('result');
                    Mage::log(
                        'DPS result from url: ' . $result,
                        null,
                        MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
                    );
                    if (empty ($result)) {
                        throw new Exception(
                            "Can't retrieve result from GET variable result. Check your server configuration."
                        );
                    }
                    $this->_processSuccessResponse($this->getRequest()->getParam('result'));
                } catch (Exception $e) {
                    Mage::logException($e);
                    Mage::log(
                        'MageBaseDps failed with exception - see exception.log',
                        null,
                        MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
                    );
                    // At this point, something's failed at our end - we should emit a 50x error so that
                    // DPS will continue to retry, rather than assuming we've succeeded and giving up.
                    // Perhaps we have a transient error connecting back.
                    Mage::app()->getResponse()
                        ->setHttpResponseCode(503)
                        ->sendResponse();
                    exit;
                }
                Mage::app()->getResponse()
                    ->setHttpResponseCode(200)
                    ->sendResponse();
                exit;
            }
            //url tampering = wrong PxPayUserId
        } else {
            Mage::log(
                'MageBaseDps successAction, but wrong PxPayUserId',
                null,
                MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
            );
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    public function failAction()
    {
        Mage::log(
            'MageBaseDps failAction',
            null,
            MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
        );
        if (!$this->_validateUserId($this->getRequest()->getParam('userid'))) {
            Mage::log(
                'MageBaseDps failAction - wrong PxPayUserId',
                null,
                MageBase_DpsPaymentExpress_Model_Method_Pxpay::DPS_LOG_FILENAME
            );
        }
        $resultXml = $this->_processFailResponse($this->getRequest()->getParam('result'));
        //reactivate quote
        $session = Mage::getSingleton('checkout/session');
        if ($session
            && (int)$resultXml->TxnData2
            && (string)$resultXml->MerchantReference
            && $session->getLastRealOrderId() == (string)$resultXml->MerchantReference
        ) {
            Mage::getModel('sales/quote')->load((int)$resultXml->TxnData2)->setIsActive(true)->save();
            $session->setLastQuoteId((int)$resultXml->TxnData2);
        }
        if ($session) {
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    protected function _getRealResponse($result)
    {
        return Mage::getSingleton('magebasedps/method_pxpay')->getRealResponse($result);
    }

    protected function _processSuccessResponse($result)
    {
        return Mage::getSingleton('magebasedps/method_pxpay')->processSuccessResponse($result);
    }

    protected function _processFailResponse($result)
    {
        return Mage::getSingleton('magebasedps/method_pxpay')->processFailResponse($result);
    }

    protected function _validateUserId($userId)
    {
        return Mage::getSingleton('magebasedps/method_pxpay')->validateUserId($userId);
    }

}
