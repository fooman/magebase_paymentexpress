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
 * @author      Tim Oliver <tim@xi.co.nz>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Provides the source model for the 'emailstosend' configuration option
 */
class MageBase_DpsPaymentExpress_Model_System_InvoiceConfig
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => MageBase_DpsPaymentExpress_Model_Method_Common::EMAIL_SEND_ORDER,
                'label' => Mage::helper('magebasedps')->__('Send Order Email Only')
            ),
            array(
                'value' => MageBase_DpsPaymentExpress_Model_Method_Common::EMAIL_SEND_INVOICE,
                'label' => Mage::helper('magebasedps')->__('Send Invoice Email Only')
            ),
            array(
                'value' => MageBase_DpsPaymentExpress_Model_Method_Common::EMAIL_SEND_BOTH,
                'label' => Mage::helper('magebasedps')->__('Send Both')
            ),
        );
    }

}