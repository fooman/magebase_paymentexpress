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

class MageBase_DpsPaymentExpress_Block_Pxpay_Info extends Mage_Payment_Block_Info
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('magebase/dps/pxpay/info.phtml');
    }

    public function toPdf()
    {
        $this->setTemplate('magebase/dps/pxpay/pdf/pxpay.phtml');
        return $this->toHtml();
    }

    public function getAdditionalData($key)
    {
        return Mage::helper('magebasedps')->getAdditionalData($this->getInfo(), $key);
    }

    public function getMaxmindData()
    {
        return Mage::helper('magebasedps')->getMaxmindData($this->getInfo());
    }

    public function wasThreeDSecure()
    {
        return Mage::helper('magebasedps')->wasThreeDSecure($this->getInfo());
    }
}
