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
use TrustPayments\Sdk\Service\TransactionInvoiceService;

class TransactionInvoice extends AbstractOrderRelatedSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'TrustPayments_Payment_Webhook_TransactionInvoice' => 'handle'
        ];
    }

    /**
     *
     * @var TransactionInvoiceService
     */
    private $transactionInvoiceService;

    /**
     *
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     */
    public function __construct(ModelManager $modelManager, ApiClient $apiClient)
    {
        parent::__construct($modelManager);
        $this->transactionInvoiceService = new TransactionInvoiceService($apiClient->getInstance());
    }

    /**
     *
     * @param WebhookRequest $request
     */
    protected function loadEntity(WebhookRequest $request)
    {
        return $this->callApi($this->transactionInvoiceService->getApiClient(), function () use ($request) {
            return $this->transactionInvoiceService->read($request->getSpaceId(), $request->getEntityId());
        });
    }

    /**
     *
     * @param \TrustPayments\Sdk\Model\TransactionInvoice $transactionInvoice
     * @return int
     */
    protected function getTransactionId($transactionInvoice)
    {
        return $transactionInvoice->getLinkedTransaction();
    }

    /**
     *
     * @param Order $order
     * @param \TrustPayments\Sdk\Model\TransactionInvoice $transactionInvoice
     */
    protected function handleOrderRelatedInner(Order $order, $transactionInvoice)
    {
        switch ($transactionInvoice->getState()) {
            case \TrustPayments\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE:
                $this->notApplicable($order, $transactionInvoice);
                break;
            case \TrustPayments\Sdk\Model\TransactionInvoiceState::PAID:
                $this->paid($order, $transactionInvoice);
                break;
            case \TrustPayments\Sdk\Model\TransactionInvoiceState::DERECOGNIZED:
                $this->derecognized($order, $transactionInvoice);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    private function notApplicable(Order $order, \TrustPayments\Sdk\Model\TransactionInvoice $transactionInvoice)
    {
        $order->setClearedDate($transactionInvoice->getCreatedOn());
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_COMPLETELY_PAID));
        $this->modelManager->flush($order);
    }

    private function paid(Order $order, \TrustPayments\Sdk\Model\TransactionInvoice $transactionInvoice)
    {
        $order->setClearedDate($transactionInvoice->getPaidOn());
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_COMPLETELY_PAID));
        $this->modelManager->flush($order);
    }

    private function derecognized(Order $order, \TrustPayments\Sdk\Model\TransactionInvoice $transactionInvoice)
    {
        $order->setPaymentStatus($this->getStatus(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED));
        $this->modelManager->flush($order);
    }

    private function getStatus($statusId)
    {
        return $this->modelManager->getRepository(Status::class)->find($statusId);
    }
}
