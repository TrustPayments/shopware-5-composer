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

use Enlight\Event\SubscriberInterface;

abstract class AbstractSubscriber implements SubscriberInterface
{
    
    /**
     * In case a \TrustPayments\Sdk\Http\ConnectionException or a \TrustPayments\Sdk\VersioningException occurs, the {@code $callback} function is called again.
     *
     * @param \TrustPayments\Sdk\ApiClient $apiClient
     * @param callable $callback
     * @throws \TrustPayments\Sdk\Http\ConnectionException
     * @throws \TrustPayments\Sdk\VersioningException
     * @return mixed
     */
    protected function callApi(\TrustPayments\Sdk\ApiClient $apiClient, $callback)
    {
        $lastException = null;
        $apiClient->setConnectionTimeout(5);
        for ($i = 0; $i < 5; $i++) {
            try {
                return $callback();
            } catch (\TrustPayments\Sdk\VersioningException $e) {
                $lastException = $e;
            } catch (\TrustPayments\Sdk\Http\ConnectionException $e) {
                $lastException = $e;
            } finally {
                $apiClient->setConnectionTimeout(20);
            }
        }
        throw $lastException;
    }
}
