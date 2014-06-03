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
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @copyright   Copyright (c) 2010 MageBase (http://www.magebase.com)
 * @copyright   Copyright (c) 2010 Fooman Ltd (http://www.fooman.co.nz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageBase_DpsPaymentExpress_Model_Method_Pxpost extends Mage_Payment_Model_Method_Cc
{
    const URL_PXPOST = 'https://sec.paymentexpress.com/pxpost.aspx';
    const DPS_LOG_FILENAME = 'magebase_dps_pxpost.log';

    protected $_code = 'magebasedpspxpost';
    protected $_formBlockType = 'magebasedps/pxpost_form';
    protected $_infoBlockType = 'magebasedps/pxpost_info';
    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = false;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded = false;
    protected $_canSaveCc = false;

    protected $_order;

    /**
     * retrieve PostUsername from database
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getPostUsername($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        return Mage::helper('core')->decrypt(
            Mage::getStoreConfig('payment/' . $this->_code . '/postusername', $storeId)
        );
    }

    /**
     * retrieve PostPassword from database
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getPostPassword($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        return Mage::helper('core')->decrypt(
            Mage::getStoreConfig('payment/' . $this->_code . '/postpassword', $storeId)
        );
    }

    /**
     * retrieve payment action from database
     * Auth or Purchase
     *
     * @param int $storeId
     *
     * @return string
     */
    protected function _getPxPostPaymentAction($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        switch (Mage::getStoreConfig('payment/' . $this->_code . '/payment_action', $storeId)) {
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE:
                return MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_AUTHORIZE;
                break;
            case Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE:
                return MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_PURCHASE;
                break;
        }
    }

    /**
     * check if current currency code is allowed for this payment method
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return Mage::helper('magebasedps')->canUseCurrency($currencyCode);
    }

    /**
     * authorize the amount to be taken later from CC
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return MageBase_DpsPaymentExpress_Model_Method_Pxpost
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $this->setAmount($amount)
            ->setPayment($payment)
            ->setPaymentAction(MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_AUTHORIZE);
        $result = $this->buildRequestAndSubmitToDps() !== false;
        if ($result) {
            $dpsTxnRef = Mage::helper('magebasedps')->getAdditionalData($payment, 'DpsTxnRef');
            $payment->setStatus(self::STATUS_APPROVED)
                ->setLastTransId($dpsTxnRef)
                ->setTransactionId($dpsTxnRef);
        } else {
            $message = Mage::helper('magebasedps')->getErrorMessage($this->getError());
            Mage::throwException($message);
        }
        return $this;
    }

    /**
     * get the money
     *
     * @param Varien_Object $payment
     * @param float         $amount
     *
     * @return MageBase_DpsPaymentExpress_Model_Method_Pxpost
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $this->setAmount($amount)
            ->setPayment($payment);
        if (Mage::helper('magebasedps')->getAdditionalData($payment, 'DpsTxnRef')) {
            $this->setPaymentAction(MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_COMPLETE);
            $complete = true;
        } else {
            $this->setPaymentAction(MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_PURCHASE);
            $complete = false;
        }

        $result = $this->buildRequestAndSubmitToDps() !== false;

        if ($result) {
            $dpsTxnRef = Mage::helper('magebasedps')->getAdditionalData($payment, 'DpsTxnRef');
            $payment->setStatus(self::STATUS_APPROVED)
                ->setLastTransId($dpsTxnRef)
                ->setTransactionId($dpsTxnRef);
            if ($complete) {
                $amount = Mage::helper('magebasedps')->getAdditionalData($payment, 'Amount');
                //using registerCaptureNotification creates a transaction record but unfortunately doubles up Total Paid
                //$payment->registerCaptureNotification($amount);
            }
        } else {
            $message = Mage::helper('magebasedps')->getErrorMessage($this->getError());
            Mage::throwException($message);
        }
        return $this;
    }

    public function refund(Varien_Object $payment, $amount)
    {
        $this->setAmount($amount)
            ->setPayment($payment)
            ->setPaymentAction(MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_REFUND);
        $result = $this->buildRequestAndSubmitToDps() !== false;
        if ($result) {
            $dpsTxnRef = Mage::helper('magebasedps')->getAdditionalData($payment, 'DpsTxnRef');
            $payment->setStatus(self::STATUS_APPROVED)
                ->setLastTransId($dpsTxnRef);
        } else {
            $message = Mage::helper('magebasedps')->getErrorMessage($this->getError());
            Mage::throwException($message);
        }
        return $this;
    }

    /**
     * create transaction object in xml and submit to server
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param boolean                        $enableAddBillCard
     * @param boolean                        $rebill
     *
     * @return SimpleXMLElement $responseXml
     */
    protected function getRequestToDpsResult($payment, $enableAddBillCard = false, $rebill = false)
    {
        $client = new Zend_Http_Client();
        $client->setUri(self::URL_PXPOST);
        $client->setConfig(
            array(
                'maxredirects' => 0,
                'timeout'      => 30,
            )
        );

        //Completing a previously authorized transaction
        //or refunding
        if ($this->getPaymentAction() == MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_COMPLETE
            || $this->getPaymentAction() == MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_REFUND
        ) {
            $xml = new SimpleXMLElement('<Txn></Txn>');
            $xml->addChild('PostUsername', htmlentities($this->getPostUsername($this->getStore())));
            $xml->addChild('PostPassword', htmlentities($this->getPostPassword($this->getStore())));
            $xml->addChild('Amount', trim(sprintf("%9.2f", $this->getAmount())));
            $xml->addChild('TxnType', $this->getPaymentAction());
            $xml->addChild('MerchantReference', $this->_getOrderId());
            $xml->addChild('DpsTxnRef', Mage::helper('magebasedps')->getAdditionalData($payment, 'DpsTxnRef'));
            if ($rebill) {
                $xml->addChild('DpsBillingId', $rebill);
            }
            $txnId = substr(uniqid(rand()), 0, 16);
            $this->setTransactionId($txnId);
            $xml->addChild('TxnId', $txnId);
        } else {
            //authorise or purchase
            $txnId = substr(uniqid(rand()), 0, 16);
            $this->setTransactionId($txnId);
            if (MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_AUTHORIZE == $this->getPaymentAction()) {
                $payment->setIsTransactionClosed(0);
            }
            $xml = new SimpleXMLElement('<Txn></Txn>');
            $xml->addChild('Amount', trim(sprintf("%9.2f", $this->getAmount())));
            $xml->addChild('CardHolderName', htmlspecialchars(trim($payment->getCcOwner()), ENT_QUOTES, 'UTF-8'));
            $xml->addChild('CardNumber', $payment->getCcNumber());
            //$xml->addChild('BillingId', '');
            $xml->addChild('Cvc2', htmlentities($payment->getCcCid()));
            $xml->addChild('Cvc2Presence', ($payment->getCcCid()) ? '1' : '0');
            $xml->addChild(
                'DateExpiry',
                str_pad($payment->getCcExpMonth(), 2, '0', STR_PAD_LEFT) . substr($payment->getCcExpYear(), 2, 2)
            );
            if ($rebill) {
                $xml->addChild('DpsBillingId', $rebill);
            }
            //$xml->addChild('DpsTxnRef', '');
            if ($enableAddBillCard) {
                $xml->addChild('EnableAddBillCard', $enableAddBillCard);
            }
            $xml->addChild('InputCurrency', $this->_getCurrencyCode());
            $xml->addChild('MerchantReference', $this->_getOrderId());
            $xml->addChild('PostUsername', htmlentities($this->getPostUsername($this->getStore())));
            $xml->addChild('PostPassword', htmlentities($this->getPostPassword($this->getStore())));
            $xml->addChild('TxnType', $this->getPaymentAction());
            //$xml->addChild('TxnData1', '');
            //$xml->addChild('TxnData2', '');
            //$xml->addChild('TxnData3', '');
            $xml->addChild('TxnId', $txnId);
            //$xml->addChild('EnableAvsData', '');
            //$xml->addChild('AvsAction', '');
            //$xml->addChild('AvsPostCode', '');
            //$xml->addChild('AvsStreetAddress', '');
            //$xml->addChild('DateStart', '');
            //$xml->addChild('IssueNumber', '');
            //$xml->addChild('Track2', '');
        }
        Mage::dispatchEvent('magebasedps_pxpost_xml_before', array('method_instance' => $this, 'xml' => $xml));
        $responseXml = $this->_requestResponse($client, $xml);

        //check if we have to send another Post to request the status of the transaction
        if ($responseXml && $responseXml->Transaction && (int)$responseXml->Transaction[0]->StatusRequired) {
            $xml = new SimpleXMLElement('<Txn></Txn>');
            $xml->addChild('PostUsername', htmlentities($this->getPostUsername($this->getStore())));
            $xml->addChild('PostPassword', htmlentities($this->getPostPassword($this->getStore())));
            $xml->addChild('TxnType', 'Status');
            $xml->addChild('TxnId', $txnId);
            $responseXml = $this->_requestResponse($client, $xml);
        }
        return $responseXml;
    }

    /**
     * create transaction object in xml and submit to server
     *
     * @return bool
     */
    public function buildRequestAndSubmitToDps()
    {
        $payment = $this->getPayment();

        $responseXml = $this->getRequestToDpsResult($payment);

        if ($responseXml && $this->_validateResponse($responseXml)) {
            $this->unsError();
            //update payment information with last transaction unless we are refunding or completing
            if ($this->getPaymentAction() != MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_REFUND
            ) {
                $this->setAdditionalData($responseXml, $payment);
            }

            return true;
        }

        return false;

    }

    /**
     * send xml as http request to server
     *
     * @param Zend_Http_Client $client
     * @param SimpleXMLElement $xml
     *
     * @return bool
     */
    protected function _requestResponse($client, $xml)
    {
        $client->setParameterPost('xml', $xml->asXML());
        if ($this->debugToDb()) {
            //mask out card number in debug log
            if ($xml->CardNumber) {
                $xml->CardNumber = substr($xml->CardNumber, 0, 6) . "........." . substr($xml->CardNumber, -1);
            }
            if ($xml->Cvc2) {
                $xml->Cvc2 = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), '*', $xml->Cvc2);
            }
            $debugDb = Mage::getModel('magebasedps/debug')
                ->setRequestBody($xml->asXML())
                ->save();
        }
        $response = $client->request('POST');
        if ($response && $response->getBody()) {
            if ($this->debugToDb()) {
                $debugDb
                    ->setResponseBody($response->getBody())
                    ->save();
            }
            return simplexml_load_string($response->getBody());
        } else {
            return false;
        }
    }


    /**
     * validate returned response
     * checks: Success Response
     *         Authorized Response
     *         amount = base grand total
     *         currency settled = base currency
     *         order exists
     *
     * @param SimpleXMLElement $resultXml
     *
     * @return bool
     */
    protected function _validateResponse($resultXml)
    {
        try {
            if ($resultXml) {
                if (!$resultXml->Transaction) {
                    Mage::log(
                        "Error in DPS Response Validation: No Transaction Data. " . $resultXml->asXML(),
                        null,
                        self::DPS_LOG_FILENAME
                    );
                    if ($resultXml->HelpText) {
                        $this->setError(array('message' => $resultXml->HelpText));
                    }
                    return false;
                }
                if ((int)$resultXml->Transaction[0]->StatusRequired) {
                    Mage::log(
                        "Error in DPS Response Validation: No correct status even after retrying.",
                        null,
                        self::DPS_LOG_FILENAME
                    );
                    return false;
                }
                if (!isset($resultXml->Transaction[0]['success'])) {
                    Mage::log(
                        "Error in DPS Response Validation: No Success Data",
                        null,
                        self::DPS_LOG_FILENAME
                    );
                    return false;
                }
                if (!(int)$resultXml->Transaction[0]['success']) {
                    $common = Mage::getModel('magebasedps/method_common');
                    $errExplained = false;
                    if (isset($resultXml->Transaction[0]['reco'])) {
                        $errExplained = $common->returnErrorExplanation($resultXml->Transaction[0]['reco']);
                        Mage::log(
                            "Error in DPS Response Validation: " .
                            $errExplained, null,
                            self::DPS_LOG_FILENAME
                        );
                    } else {
                        Mage::log(
                            "Error in DPS Response Validation: No reco code.", null, self::DPS_LOG_FILENAME
                        );
                    }
                    if ($errExplained) {
                        $this->setError(array('message' => $errExplained));
                    } elseif ($resultXml->HelpText) {
                        $this->setError(array('message' => $resultXml->HelpText));
                    }
                    return false;
                }
                $order = $this->_getOrder();
                if (!$order) {
                    Mage::log("Error in DPS Response Validation: No Order", null, self::DPS_LOG_FILENAME);
                    return false;
                }
                if ($this->getPaymentAction() != MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_COMPLETE
                    && $this->getPaymentAction() != MageBase_DpsPaymentExpress_Model_Method_Common::ACTION_REFUND
                ) {
                    if (abs((float)$resultXml->Transaction[0]->Amount - sprintf("%9.2f", $order->getBaseGrandTotal()))
                        > 0.0005
                    ) {
                        Mage::log("Error in DPS Response Validation: Mismatched totals", null, self::DPS_LOG_FILENAME);
                        return false;
                    }
                }
                if ((float)$resultXml->Transaction[0]->CurrencySettlement != $order->getBaseCurrencyCode()) {
                    Mage::log("Error in DPS Response Validation: Mismatched currencies", null, self::DPS_LOG_FILENAME);
                    return false;
                }
                if ((string)$resultXml->Transaction[0]->Cvc2ResultCode == 'N') {
                    Mage::log("Error in DPS Response Validation: Mismatched CVC", null, self::DPS_LOG_FILENAME);
                    return false;
                }
                return true;
            } else {
                Mage::log("Error in DPS Response Validation: Not a valid response.", null, self::DPS_LOG_FILENAME);
                return false;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log("Error in DPS Response Validation " . $e->getMessage(), null, self::DPS_LOG_FILENAME);
            return false;
        }

    }

    /**
     * get flag if we should log debug info to database
     *
     * @return bool
     */
    public function debugToDb()
    {
        return Mage::getStoreConfig('payment/' . $this->_code . '/debug', $this->getStore());
    }

    /**
     * save all useful returned info from DPS to additional information field
     * on order payment object
     *
     * @param SimpleXMLElement               $responseXml
     * @param Mage_Sales_Model_Order_Payment $payment ?
     *
     * @return void
     */
    public function setAdditionalData($responseXml, $payment)
    {
        $data = array(
            'ReCo'           => (string)$responseXml->Transaction[0]['reco'],
            'AuthCode'       => (string)$responseXml->Transaction[0]->AuthCode,
            'CardName'       => (string)$responseXml->Transaction[0]->CardName,
            'CurrencyName'   => (string)$responseXml->Transaction[0]->CurrencyName,
            'Amount'         => (string)$responseXml->Transaction[0]->Amount,
            'CardHolderName' => (string)$responseXml->Transaction[0]->CardHolderName,
            'CardNumber'     => (string)$responseXml->Transaction[0]->CardNumber,
            'CardNumber2'    => (string)$responseXml->Transaction[0]->CardNumber2,
            'TxnType'        => (string)$responseXml->Transaction[0]->TxnType,
            'TransactionId'  => (string)$responseXml->Transaction[0]->TransactionId,
            'DpsTxnRef'      => (string)$responseXml->Transaction[0]->DpsTxnRef,
            'BillingId'      => (string)$responseXml->Transaction[0]->BillingId,
            'DpsBillingId'   => (string)$responseXml->Transaction[0]->DpsBillingId,
            'TxnMac'         => (string)$responseXml->Transaction[0]->TxnMac,
            'ResponseText'   => (string)$responseXml->ResponseText,
            'HelpText'       => (string)$responseXml->HelpText,
            'AcquirerTxnRef' => (string)$responseXml->Transaction[0]->AcquirerTxnRef,
            'Cvc2ResultCode' => (string)$responseXml->Transaction[0]->Cvc2ResultCode,
            'DateExpiry'     => (string)$responseXml->Transaction[0]->DateExpiry
        );
        Mage::helper('magebasedps')->setAdditionalData($payment, $data);
    }

    public function OtherCcType($type)
    {
        return $type == 'OT' || $type == 'DIN';
    }

    public function getVerificationRegEx()
    {
        $verificationExpList = parent::getVerificationRegEx();
        $verificationExpList['DIN'] = $verificationExpList ['OT'];
        return $verificationExpList;
    }

    /**
     * Order increment ID getter (either real from order or a reserved from quote)
     *
     * @return string
     */
    protected function _getOrderId()
    {
        $info = $this->getInfoInstance();

        if ($this->_isPlaceOrder()) {
            return $info->getOrder()->getIncrementId();
        } else {
            if (!$info->getQuote()->getReservedOrderId()) {
                $info->getQuote()->reserveOrderId();
            }
            return $info->getQuote()->getReservedOrderId();
        }
    }

    /**
     * Grand total getter
     *
     * @return string
     */
    protected function _getAmount()
    {
        $info = $this->getInfoInstance();
        if ($this->_isPlaceOrder()) {
            return (double)$info->getOrder()->getQuoteBaseGrandTotal();
        } else {
            return (double)$info->getQuote()->getBaseGrandTotal();
        }
    }

    /**
     * Currency code getter
     *
     * @return string
     */
    protected function _getCurrencyCode()
    {
        $info = $this->getInfoInstance();

        if ($this->_isPlaceOrder()) {
            return $info->getOrder()->getBaseCurrencyCode();
        } else {
            return $info->getQuote()->getBaseCurrencyCode();
        }
    }

    /**
     * Whether current operation is order placement
     *
     * @return bool
     */
    protected function _isPlaceOrder()
    {
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            return false;
        } elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
            return true;
        }
    }

    /**
     * retrieve current order
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    public function getStore()
    {
        $store = parent::getStore();
        if ($store instanceof Mage_Core_Model_Store) {
            $store = $store->getId();
        }
        return $store;
    }

    /**
     * Can be used in regular checkout
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        $this->_canUseCheckout = Mage::getStoreConfigFlag(
            'payment/' . $this->_code . '/frontend_checkout', $this->getStore()
        );
        return $this->_canUseCheckout;
    }
}
