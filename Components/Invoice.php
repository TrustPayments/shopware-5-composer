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
use TrustPayments\Sdk\Model\EntityQuery;
use TrustPayments\Sdk\Service\TransactionInvoiceService;

class Invoice extends AbstractService
{

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var TransactionInvoiceService
     */
    private $invoiceService;

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
        $this->invoiceService = new TransactionInvoiceService($apiClient->getInstance());
    }

    /**
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return \TrustPayments\Sdk\Model\TransactionInvoice
     */
    public function getInvoice($spaceId, $transactionId)
    {
        return $this->callApi($this->invoiceService->getApiClient(), function () use ($spaceId, $transactionId) {
            $query = new EntityQuery();
            $query->setFilter($this->createEntityFilter('completion.lineItemVersion.transaction.id', $transactionId));
            $query->setNumberOfEntities(1);
            return current($this->invoiceService->search($spaceId, $query));
        });
    }
}
