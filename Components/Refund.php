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

namespace TrustPaymentsPayment\Components;

use Shopware\Components\Model\ModelManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TrustPayments\Sdk\Service\RefundService;
use TrustPayments\Sdk\Model\EntityQuery;
use Shopware\Models\Order\Order;

class Refund extends AbstractService
{

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var RefundService
     */
    private $refundService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ApiClient $apiClient
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ApiClient $apiClient)
    {
        parent::__construct($container);
        $this->modelManager = $modelManager;
        $this->refundService = new RefundService($apiClient->getInstance());
    }

    /**
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \TrustPayments\Sdk\Model\Refund[]
     */
    public function getRefunds($spaceId, $transactionId)
    {
        return $this->callApi($this->refundService->getApiClient(), function () use ($spaceId, $transactionId) {
            $query = new EntityQuery();
            $query->setFilter($this->createEntityFilter('transaction.id', $transactionId));
            $query->setOrderBys([
                $this->createEntityOrderBy('createdOn', \TrustPayments\Sdk\Model\EntityQueryOrderByType::DESC)
            ]);
            $query->setNumberOfEntities(50);
            return $this->refundService->search($spaceId, $query);
        });
    }

    /**
     *
     * @param \TrustPayments\Sdk\Model\TransactionInvoice $invoice
     * @param \TrustPayments\Sdk\Model\Refund[] $refunds
     * @return \TrustPayments\Sdk\Model\LineItem[]
     */
    public function getRefundBaseLineItems(\TrustPayments\Sdk\Model\TransactionInvoice $invoice = null, array $refunds = [])
    {
        $refund = $this->getLastSuccessfulRefund($refunds);
        if ($refund) {
            return $refund->getReducedLineItems();
        } elseif ($invoice != null) {
            return $invoice->getLineItems();
        } else {
            return [];
        }
    }

    /**
     *
     * @param \TrustPayments\Sdk\Model\Refund[] $refunds
     */
    private function getLastSuccessfulRefund(array $refunds)
    {
        foreach ($refunds as $refund) {
            if ($refund->getState() == \TrustPayments\Sdk\Model\RefundState::SUCCESSFUL) {
                return $refund;
            }
        }
        return false;
    }

    /**
     *
     * @param Order $order
     * @param \TrustPayments\Sdk\Model\Transaction $transaction
     * @param array $reductions
     */
    public function createRefund(Order $order, \TrustPayments\Sdk\Model\Transaction $transaction, array $reductions)
    {
        $refund = new \TrustPayments\Sdk\Model\RefundCreate();
        $refund->setExternalId(uniqid($order->getNumber() . '-'));
        $refund->setReductions($reductions);
        $refund->setTransaction($transaction->getId());
        $refund->setType(\TrustPayments\Sdk\Model\RefundType::MERCHANT_INITIATED_ONLINE);
        return $refund;
    }

    /**
     *
     * @param int $spaceId
     * @param \TrustPayments\Sdk\Model\RefundCreate $refundRequest
     * @return \TrustPayments\Sdk\Model\Refund
     */
    public function refund($spaceId, \TrustPayments\Sdk\Model\RefundCreate $refundRequest)
    {
        return $this->refundService->refund($spaceId, $refundRequest);
    }
}
