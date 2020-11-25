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

namespace TrustPaymentsPayment\Components\Provider;

use TrustPaymentsPayment\Components\ApiClient;

/**
 * Provider of payment connector information from the gateway.
 */
class PaymentConnector extends AbstractProvider
{

    /**
     * Constructor.
     *
     * @param \TrustPayments\Sdk\ApiClient $apiClient
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(ApiClient $apiClient, \Zend_Cache_Core $cache)
    {
        parent::__construct($apiClient->getInstance(), $cache, 'trustpayments_payment_connectors');
    }

    /**
     * Returns the payment connector by the given id.
     *
     * @param int $id
     * @return \TrustPayments\Sdk\Model\PaymentConnector
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment connectors.
     *
     * @return \TrustPayments\Sdk\Model\PaymentConnector[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $methodService = new \TrustPayments\Sdk\Service\PaymentConnectorService($this->apiClient);
        return $methodService->all();
    }

    protected function getId($entry)
    {
        /* @var \TrustPayments\Sdk\Model\PaymentConnector $entry */
        return $entry->getId();
    }
}
