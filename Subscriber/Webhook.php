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

namespace TrustPaymentsPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use TrustPaymentsPayment\Components\Webhook as WebhookService;

class Webhook implements SubscriberInterface
{

    /**
     *
     * @var WebhookService
     */
    private $webhookService;

    public static function getSubscribedEvents()
    {
        return [
            'TrustPayments_Payment_Config_Synchronize' => 'onSynchronize'
        ];
    }

    /**
     * Constructor.
     *
     * @param WebhookService $webhookService
     */
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function onSynchronize()
    {
        $this->webhookService->install();
    }
}
