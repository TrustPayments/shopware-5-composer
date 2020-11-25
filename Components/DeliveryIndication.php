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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Components\Model\ModelManager;
use TrustPaymentsPayment\Models\TransactionInfo as TransactionInfoModel;

class DeliveryIndication extends AbstractService
{

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var \TrustPayments\Sdk\Service\DeliveryIndicationService
     */
    private $deliveryIndicationService;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param ConfigReader $configReader
     * @param ApiClient $apiClient
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, ConfigReader $configReader, ApiClient $apiClient)
    {
        parent::__construct($container);
        $this->modelManager = $modelManager;
        $this->configReader = $configReader;
        $this->deliveryIndicationService = new \TrustPayments\Sdk\Service\DeliveryIndicationService($apiClient->getInstance());
    }

    /**
     *
     * @param TransactionInfoModel $transactionInfo
     * @return \TrustPayments\Sdk\Model\DeliveryIndication[]
     */
    public function getDeliveryIndication(TransactionInfoModel $transactionInfo)
    {
        return $this->callApi($this->deliveryIndicationService->getApiClient(), function () use ($transactionInfo) {
            $query = new \TrustPayments\Sdk\Model\EntityQuery();
            $query->setFilter($this->createEntityFilter('transaction.id', $transactionInfo->getTransactionId()));
            $result = $this->deliveryIndicationService->search($transactionInfo->getSpaceId(), $query);
            if (count($result) == 1) {
                return current($result);
            }
        });
    }

    public function accept(TransactionInfoModel $transactionInfo)
    {
        $deliveryIndication = $this->getDeliveryIndication($transactionInfo);
        if ($deliveryIndication == null) {
            throw new \Exception('No delivery indication in space ' . $transactionInfo->getSpaceId() . ' for transaction ' . $transactionInfo->getTransactionId() . ' found.');
        }
        $this->deliveryIndicationService->markAsSuitable($transactionInfo->getSpaceId(), $deliveryIndication->getId());

        $this->container->get('trustpayments_payment.transaction')->handleTransactionState($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
    }

    public function deny(TransactionInfoModel $transactionInfo)
    {
        $deliveryIndication = $this->getDeliveryIndication($transactionInfo);
        if ($deliveryIndication == null) {
            throw new \Exception('No delivery indication in space ' . $transactionInfo->getSpaceId() . ' for transaction ' . $transactionInfo->getTransactionId() . ' found.');
        }
        $this->deliveryIndicationService->markAsNotSuitable($transactionInfo->getSpaceId(), $deliveryIndication->getId());

        $this->container->get('trustpayments_payment.transaction')->handleTransactionState($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
    }
}
