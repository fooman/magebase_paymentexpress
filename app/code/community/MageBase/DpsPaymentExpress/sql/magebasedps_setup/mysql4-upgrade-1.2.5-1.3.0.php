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

function createNewStatus($newOrderStatus)
{
    $status = Mage::getModel('sales/order_status')->load($newOrderStatus['status']);
    if ($status->getId()) {
        //skip existing
        return;
    }
    $status = Mage::getModel('sales/order_status');
    $status->setStatus($newOrderStatus['status']);
    $status->setLabel($newOrderStatus['label']);
    $status->save();
    $status->assignState($newOrderStatus['state'], 0);
}

$installer = $this;
/* @var $installer MageBase_DpsPaymentExpress_Model_Mysql4_Setup */

$installer->startSetup();
if (version_compare(Mage::getVersion(), '1.5.0.0', '>=')) {
    $newOrderStatusses =  array();
    $newOrderStatusses[] =  array(
        'status'=> 'pending_dps',
        'label'=> 'Pending Payment (DPS)',
        'state'=> 'pending_payment'
    );
    $newOrderStatusses[] =  array(
        'status'=> 'processing_dps_auth',
        'label'=> 'Processing (DPS - Amount authorised)',
        'state'=> 'processing'
    );
    $newOrderStatusses[] =  array(
        'status'=> 'processing_dps_paid',
        'label'=> 'Processing (DPS - Amount paid)',
        'state'=> 'processing'
    );

    foreach ($newOrderStatusses as $newOrderStatus) {
        createNewStatus($newOrderStatus);
    }
}
$installer->endSetup();
