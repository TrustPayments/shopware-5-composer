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
use TrustPaymentsPayment\Components\Webhook\Entity;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Shop\Shop;
use Shopware\Components\Model\ModelManager;

class Webhook extends AbstractService
{

    /**
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var \TrustPayments\Sdk\ApiClient
     */
    private $apiClient;

    /**
     * The transaction url API service.
     *
     * @var \TrustPayments\Sdk\Service\WebhookUrlService
     */
    private $webhookUrlService;

    /**
     * The transaction listener API service.
     *
     * @var \TrustPayments\Sdk\Service\WebhookListenerService
     */
    private $webhookListenerService;

    private $webhookEntities = array();

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
        $this->apiClient = $apiClient->getInstance();
        $this->webhookUrlService = new \TrustPayments\Sdk\Service\WebhookUrlService($this->apiClient);
        $this->webhookListenerService = new \TrustPayments\Sdk\Service\WebhookListenerService($this->apiClient);

        $this->webhookEntities[] = new Entity(1487165678181, 'Manual Task', array(
            \TrustPayments\Sdk\Model\ManualTaskState::DONE,
            \TrustPayments\Sdk\Model\ManualTaskState::EXPIRED,
            \TrustPayments\Sdk\Model\ManualTaskState::OPEN
        ));
        $this->webhookEntities[] = new Entity(1472041857405, 'Payment Method Configuration', array(
            \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE,
            \TrustPayments\Sdk\Model\CreationEntityState::DELETED,
            \TrustPayments\Sdk\Model\CreationEntityState::DELETING,
            \TrustPayments\Sdk\Model\CreationEntityState::INACTIVE
        ), true);
        $this->webhookEntities[] = new Entity(1472041829003, 'Transaction', array(
            \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED,
            \TrustPayments\Sdk\Model\TransactionState::DECLINE,
            \TrustPayments\Sdk\Model\TransactionState::FAILED,
            \TrustPayments\Sdk\Model\TransactionState::FULFILL,
            \TrustPayments\Sdk\Model\TransactionState::VOIDED,
            \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
            \TrustPayments\Sdk\Model\TransactionState::PROCESSING,
            \TrustPayments\Sdk\Model\TransactionState::CONFIRMED
        ));
        $this->webhookEntities[] = new Entity(1472041819799, 'Delivery Indication', array(
            \TrustPayments\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED
        ));
        $this->webhookEntities[] = new Entity(1472041816898, 'Transaction Invoice', array(
            \TrustPayments\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE,
            \TrustPayments\Sdk\Model\TransactionInvoiceState::PAID,
            \TrustPayments\Sdk\Model\TransactionInvoiceState::DERECOGNIZED
        ));
    }

    /**
     * Installs the necessary webhooks in Trust Payments.
     */
    public function install()
    {
        $spaceIds = array();
        foreach ($this->modelManager->getRepository(Shop::class)->findAll() as $shop) {
            $pluginConfig = $this->configReader->getByPluginName('TrustPaymentsPayment', $shop);
            $spaceId = $pluginConfig['spaceId'];
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $webhookUrl = $this->getWebhookUrl($spaceId);
                if ($webhookUrl == null) {
                    $webhookUrl = $this->createWebhookUrl($spaceId);
                }

                $existingListeners = $this->getWebhookListeners($spaceId, $webhookUrl);
                foreach ($this->webhookEntities as $webhookEntity) {
                    /* @var Entity $webhookEntity */
                    $exists = false;
                    foreach ($existingListeners as $existingListener) {
                        if ($existingListener->getEntity() == $webhookEntity->getId()) {
                            $exists = true;
                        }
                    }

                    if (! $exists) {
                        $this->createWebhookListener($webhookEntity, $spaceId, $webhookUrl);
                    }
                }
                $spaceIds[] = $spaceId;
            }
        }
    }

    /**
     * Create a webhook listener.
     *
     * @param Entity $entity
     * @param int $spaceId
     * @param \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl
     * @return \TrustPayments\Sdk\Model\WebhookListenerCreate
     */
    private function createWebhookListener(Entity $entity, $spaceId, \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $webhookListener = new \TrustPayments\Sdk\Model\WebhookListenerCreate();
        $webhookListener->setEntity($entity->getId());
        $webhookListener->setEntityStates($entity->getStates());
        $webhookListener->setName('Shopware ' . $entity->getName());
        $webhookListener->setState(\TrustPayments\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookListener->setUrl($webhookUrl->getId());
        $webhookListener->setNotifyEveryChange($entity->isNotifyEveryChange());
        return $this->webhookListenerService->create($spaceId, $webhookListener);
    }

    /**
     * Returns the existing webhook listeners.
     *
     * @param int $spaceId
     * @param \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl
     * @return \TrustPayments\Sdk\Model\WebhookListener[]
     */
    private function getWebhookListeners($spaceId, \TrustPayments\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();
        $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
        $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(array(
            $this->createEntityFilter('state', \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE),
            $this->createEntityFilter('url.id', $webhookUrl->getId())
        ));
        $query->setFilter($filter);
        return $this->webhookListenerService->search($spaceId, $query);
    }

    /**
     * Creates a webhook url.
     *
     * @param int $spaceId
     * @return \TrustPayments\Sdk\Model\WebhookUrlCreate
     */
    private function createWebhookUrl($spaceId)
    {
        $webhookUrl = new \TrustPayments\Sdk\Model\WebhookUrlCreate();
        $webhookUrl->setUrl($this->getHandleUrl());
        $webhookUrl->setState(\TrustPayments\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookUrl->setName('Shopware 5');
        return $this->webhookUrlService->create($spaceId, $webhookUrl);
    }

    /**
     * Returns the existing webhook url if there is one.
     *
     * @param int $spaceId
     * @return \TrustPayments\Sdk\Model\WebhookUrl
     */
    private function getWebhookUrl($spaceId)
    {
        $query = new \TrustPayments\Sdk\Model\EntityQuery();
        $query->setNumberOfEntities(1);
        $filter = new \TrustPayments\Sdk\Model\EntityQueryFilter();
        $filter->setType(\TrustPayments\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(array(
            $this->createEntityFilter('state', \TrustPayments\Sdk\Model\CreationEntityState::ACTIVE),
            $this->createEntityFilter('url', $this->getHandleUrl())
        ));
        $query->setFilter($filter);
        $result = $this->webhookUrlService->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Returns the webhook endpoint URL.
     *
     * @return string
     */
    private function getHandleUrl()
    {
        return $this->getUrl('TrustPaymentsPaymentWebhook', 'handle');
    }
}
