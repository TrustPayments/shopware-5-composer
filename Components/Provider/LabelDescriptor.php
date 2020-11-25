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
 * Provider of label descriptor information from the gateway.
 */
class LabelDescriptor extends AbstractProvider
{

    /**
     * Constructor.
     *
     * @param \TrustPayments\Sdk\ApiClient $apiClient
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(ApiClient $apiClient, \Zend_Cache_Core $cache)
    {
        parent::__construct($apiClient->getInstance(), $cache, 'trustpayments_payment_label_descriptors');
    }

    /**
     * Returns the label descriptor by the given code.
     *
     * @param int $code
     * @return \TrustPayments\Sdk\Model\LabelDescriptor
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns a list of label descriptors.
     *
     * @return \TrustPayments\Sdk\Model\LabelDescriptor[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $labelDescriptorService= new \TrustPayments\Sdk\Service\LabelDescriptionService($this->apiClient);
        return $labelDescriptorService->all();
    }

    protected function getId($entry)
    {
        /* @var \TrustPayments\Sdk\Model\LabelDescriptor $entry */
        return $entry->getId();
    }
}
