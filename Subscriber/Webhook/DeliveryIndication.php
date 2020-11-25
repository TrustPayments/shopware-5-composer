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

namespace TrustPaymentsPayment\Subscriber\Webhook;

use TrustPaymentsPayment\Components\Webhook\Request as WebhookRequest;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use TrustPaymentsPayment\Components\ApiClient;
use TrustPayments\Sdk\Service\DeliveryIndicationService;

class DeliveryIndication extends AbstractOrderRelatedSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'TrustPayments_Payment_Webhook_DeliveryIndication' => 'handle'
        ];
    }

    /**
     *
     * @var DeliveryIndicationService
     */
    private $deliveryIndicationService;

    /**
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     */
    public function __construct(ModelManager $modelManager, ApiClient $apiClient)
    {
        parent::__construct($modelManager);
        $this->deliveryIndicationService = new DeliveryIndicationService($apiClient->getInstance());
    }

    /**
     *
     * @param WebhookRequest $request
     * @return \TrustPayments\Sdk\Model\DeliveryIndication
     */
    protected function loadEntity(WebhookRequest $request)
    {
        return $this->callApi($this->deliveryIndicationService->getApiClient(), function () use ($request) {
            $this->deliveryIndicationService->read($request->getSpaceId(), $request->getEntityId());
        });
    }

    /**
     *
     * @param \TrustPayments\Sdk\Model\DeliveryIndication $deliveryIndication
     * @return int
     */
    protected function getTransactionId($deliveryIndication)
    {
        return $deliveryIndication->getLinkedTransaction();
    }

    /**
     *
     * @param Order $order
     * @param \TrustPayments\Sdk\Model\DeliveryIndication $deliveryIndication
     */
    protected function handleOrderRelatedInner(Order $order, $deliveryIndication)
    {
        switch ($deliveryIndication->getState()) {
            case \TrustPayments\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
                $this->review($order, $deliveryIndication);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    private function review(Order $order, \TrustPayments\Sdk\Model\DeliveryIndication $deliveryIndication)
    {
        $order->setOrderStatus($this->getStatus(Status::ORDER_STATE_CLARIFICATION_REQUIRED));
        $this->modelManager->flush($order);
    }

    private function getStatus($statusId)
    {
        return $this->modelManager->getRepository(Status::class)->find($statusId);
    }
}
