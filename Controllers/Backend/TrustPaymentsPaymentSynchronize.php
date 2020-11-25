<?php

/**
 * Trust Payments Shopware 5
 *
 * This Shopware 5 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

use TrustPaymentsPayment\Components\Controller\Backend;

class Shopware_Controllers_Backend_TrustPaymentsPaymentSynchronize extends Backend
{
    public function synchronizeAction()
    {
        $pluginConfig = $this->get('shopware.plugin.config_reader')->getByPluginName('TrustPaymentsPayment');
        $userId = $pluginConfig['applicationUserId'];
        $applicationKey = $pluginConfig['applicationUserKey'];
        if ($userId && $applicationKey) {
            try {
                $this->get('events')->notify('TrustPayments_Payment_Config_Synchronize');

                $this->view->assign([
                    'success' => true
                ]);
            } catch (\Exception $e) {
                $this->view->assign([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            $this->view->assign([
                'success' => false,
                'message' => $this->get('snippets')->getNamespace('backend/trustpayments_payment/main')->get('synchronize/message/config_incomplete', 'The configuration is incomplete.')
            ]);
        }
    }
}
