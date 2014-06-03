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

class MageBase_DpsPaymentExpress_Model_Method_Common extends Mage_Payment_Model_Method_Abstract
{

    /*
    Auth        Authorizes a transactions. Must be completed within 7 days using the "Complete" TxnType.
    Complete    Completes (settles) a pre-approved Auth Transaction. The DpsTxnRef value returned
                by the original approved Auth transaction must be supplied.
    Purchase    Purchase - Funds are transferred immediately.
    Refund      Refund - Funds transferred immediately. Must be enabled as a special option.
    Validate    Validation Transaction. Effects a $1.00 Auth to validate card details including expiry date.
                Often utilised with the EnableAddBillCard property set to 1 to automatically
                add to Billing Database if the transaction is approved.
     */
    const ACTION_AUTHORIZE = 'Auth';
    const ACTION_COMPLETE = 'Complete';
    const ACTION_PURCHASE = 'Purchase';
    const ACTION_REFUND = 'Refund';
    const ACTION_VALIDATE = 'Validate';

    /**
     * Credit Card Logos
     */
    const LOGOFILE_VISA = 'VisaLogo.png';
    const LOGOFILE_VISAVERIFIED = 'VisaVerifiedLogo.png';
    const LOGOFILE_MASTERCARD = 'MasterCardLogo.png';
    const LOGOFILE_MASTERCARDSECURE = 'MCSecureCodeLogo.png';
    const LOGOFILE_AMEX = 'AmexLogo.png';
    const LOGOFILE_JCB = 'JCBLogo.png';
    const LOGOFILE_DINERS = 'DinersLogo.png';


    const STATUS_ERROR = 0;
    const STATUS_OK_INVOICE = 2;
    const STATUS_OK_DONT_INVOICE = 3;
    const STATUS_OK_ALREADY_INVOICED = 4;

    const EMAIL_SEND_ORDER = 'send_order';
    const EMAIL_SEND_INVOICE = 'send_invoice';
    const EMAIL_SEND_BOTH = 'send_both';

    /**
     * Error Codes
     * Code =>  Description
     */
    public $errorCodes
        = array(
            '51' => 'Card with Insufficient Funds',
            '54' => 'Expired Card',
            'IC' => 'Invalid Key or Username. Also check that if a TxnId is being supplied that it is unique.',
            'ID' => 'Invalid transaction type. Esure that the transaction type is either Auth or Purchase.',
            'IK' => 'Invalid UrlSuccess. Ensure that the URL being supplied does not contain a query string.',
            'IL' => 'Invalid UrlFail. Ensure that the URL being supplied does not contain a query string.',
            'IM' => 'Invalid PxPayUserId.',
            'IN' => 'Blank PxPayUserId.',
            'IP' => 'Invalid parameter. Ensure that only documented properties are being supplied.',
            'IQ' => 'Invalid TxnType. Ensure that the transaction type being submitted is either "Auth" or "Purchase".',
            'IT' => 'Invalid currency. Ensure that the CurrencyInput is correct and in the correct format e.g. "USD".',
            'IU' => 'Invalid AmountInput. Ensure that the amount is in the correct format e.g. "20.00".',
            'NF' => 'Invalid Username.',
            'NK' => 'Request not found. Check the key and the mcrypt library if in use.',
            'NL' => 'User not enabled. Contact DPS.',
            'NM' => 'User not enabled. Contact DPS.',
            'NN' => 'Invalid MAC.',
            'NO' => 'Request contains non ASCII characters.',
            'NP' => 'PXPay Closing Request tag not found.',
            'NQ' => 'User not enabled for PxPay. Contact DPS.',
            'NT' => 'Key is not 64 characters.',
            'U5' => 'Invalid User / Password',
            'U9' => 'Timeout for Transaction',
            'QD' => 'The transaction was Declined.', //Invalid TxnRef
            'Q4' => 'Invalid Amount Entered. Transaction has not been Approved',
            'Q8' => 'Invalid Currency',
            'QG' => 'Invalid TxnType',
            'QI' => 'Invalid Expiry Date (month not between 1-12)',
            'QJ' => 'Invalid Expiry Date (non numeric value submitted)',
            'QK' => 'Invalid Card Number Length',
            'QL' => 'Invalid Card Number',
            'JC' => 'Invalid BillingId',
            'JD' => 'Invalid DPSBillingId',
            'JE' => 'DPSBillingId not matched',
            'D2' => 'Invalid username',
            'D3' => 'Invalid / missing Password',
            'D4' => 'Maximum number of logon attempts exceeded'
        );

    public function returnErrorExplanation($code)
    {
        $code = (string)$code;
        if (isset($this->errorCodes[$code])) {
            return $this->errorCodes[$code];
        } else {
            return "Failed with unknown error code " . $code;
        }
    }
}
