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

class Registry
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var array
     */
    private $registry = [];

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get($key)
    {
        if (isset($this->registry[$key])) {
            return $this->registry[$key];
        } else {
            return null;
        }
    }

    public function set($key, $value)
    {
        $this->registry[$key] = $value;
    }

    public function remove($key)
    {
        if (isset($this->registry[$key])) {
            unset($this->registry[$key]);
        }
    }
}
