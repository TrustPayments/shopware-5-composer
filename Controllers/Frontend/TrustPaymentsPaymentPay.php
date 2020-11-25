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

use TrustPaymentsPayment\Components\Controller\Frontend;

class Shopware_Controllers_Frontend_TrustPaymentsPaymentPay extends Frontend
{
    public function indexAction()
    {
        $namespace = $this->container->get('snippets')->getNamespace('frontend/trustpayments_payment/main');
        return $this->forward('confirm', 'checkout', null, ['trustPaymentsErrors' => $namespace->get('checkout/javascript_error', 'The payment information could not be sent to Trust Payments. Either certain Javascript files were not included or a Javascript error occurred.')]);
    }
}
