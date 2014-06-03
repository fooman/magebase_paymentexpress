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

class MageBase_DpsPaymentExpress_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Currency codes supported by DPS
     *
     * @var array
     * @see http://www.paymentexpress.com/Knowledge_Base/Merchant_Info/Multi_Currency
     */
    protected $_supportedCurrencies
        = array(
            'AUD', //Australian Dollar
            'BRL', //Brazil Real
            'BND', //Brunei Dollar
            'CAD', //Canadian Dollar
            'CNY', //Chinese Yuan Renminbi
            'CZK', //Czech Korunaor
            'DKK', //Danish Kroner
            'EGP', //Egyptian Pound
            'EUR', //Euros
            'FJD', //Fiji Dollar
            'HKD', //Hong Kong Dollar
            'HUF', //Hungarian Forint
            'INR', //Indian Rupee
            'IDR', //Indonesia Rupiah
            'JPY', //Japanese Yen
            'KRW', //Korean Won
            'MOP', //Macau Pataca
            'MYR', //Malaysian Ringgit
            'MUR', //Mauritius Rupee
            'ANG', //Netherlands Guilder
            'TWD', //New Taiwan Dollar
            'NOK', //Norwegian Kronor
            'NZD', //New Zealand Dollar
            'PGK', //Papua New Guinea Kina
            'PHP', //Philippine Peso
            'PLN', //Polish Zloty
            'GBP', //Pound Sterling
            'PKR', //Pakistan Rupee
            'WST', //Samoan Tala
            'SAR', //Saudi Riyal
            'SBD', //Solomon Islands Dollar
            'LKR', //Sri Lankan Rupee
            'SGD', //Singapore Dollar
            'ZAR', //South African Rand
            'SEK', //Swedish Kronor
            'CHF', //Swiss Franc
            'TWD', //Taiwan Dollar
            'THB', //Thai Baht
            'TOP', //Tongan Pa'anga
            'AED', //UAE Dirham
            'USD', //United States Dollar
            'VUV' //Vanuatu Vatu
        );

    public function canUseCurrency($currencyCode)
    {
        return in_array($currencyCode, $this->_supportedCurrencies);
    }

    public function getAdditionalData($info, $key = null)
    {
        $data = array();
        if (!is_null($key) && method_exists($info, 'getAdditionalInformation')
            && $info->getAdditionalInformation($key)
        ) {
            return $info->getAdditionalInformation($key);
        }
        //We make the following check since we may get string "Array" in additional_data field. Seems like it happens
        //by default if additional_data is not set. This should be investigated but for now this is a solution.
        if ($info->getAdditionalData() && strpos($info->getAdditionalData(), 'Array') !== 0) {
            $data = unserialize($info->getAdditionalData());
        }
        if (!empty($key) && isset($data[$key])) {
            return $data[$key];
        } else {
            return '';
        }
    }

    public function setAdditionalData($info, $data, $key = null)
    {
        if (method_exists($info, 'setAdditionalInformation')) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $info->setAdditionalInformation($key, $value);
                }
            } elseif (!is_null($key)) {
                $info->setAdditionalInformation($key, $data);
            }
        }
        if (is_array($data)) {
            $info->setAdditionalData(serialize($data));
        } elseif (!is_null($key)) {
            if ($info->getAdditionalData()) {
                $existingData = unserialize($info->getAdditionalData());
            } else {
                $existingData = array();
            }
            $existingData[$key] = $data;
            $info->setAdditionalData(serialize($existingData));
        }
    }

    public function wasThreeDSecure($info)
    {
        if ($this->getAdditionalData($info, 'centinel_mpivendor') == 'Y') {
            return true;
        }
        return false;
    }

    public function getMaxmindData($info)
    {
        return false;
    }

    public function getInvoiceForTransactionId($order, $transactionId = false)
    {
        if (!$transactionId) {
            return false;
        }
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->getTransactionId() == $transactionId) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }
    }

    public function getErrorMessage($error = array())
    {
        if (isset($error['message'])) {
            $message = Mage::helper('magebasedps')->__(
                'There has been an error processing your payment (%s). Please try again later or contact us for help.',
                $error['message']
            );
        } else {
            $message = Mage::helper('magebasedps')->__(
                'There has been an error processing your payment. Please try later or contact us for help.'
            );
        }
        return $message;
    }
}
