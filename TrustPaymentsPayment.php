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

namespace TrustPaymentsPayment;

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TrustPaymentsPayment\Models\OrderTransactionMapping;
use TrustPaymentsPayment\Models\PaymentMethodConfiguration;
use TrustPaymentsPayment\Models\TransactionInfo;
use Shopware\Models\Widget\Widget;
use Shopware\Components\Plugin\Context\ActivateContext;

if (\file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

class TrustPaymentsPayment extends Plugin
{

    public function install(InstallContext $context)
    {
        parent::install($context);
        $this->updateSchema();
        $this->installWidgets($context);
    }

    public function update(UpdateContext $context)
    {
        parent::update($context);
        $this->updateSchema();
    }

    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);
//         $this->uninstallSchema();
        $this->uninstallWidgets($context);
    }
    
    public function activate(ActivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
    }

    public function build(ContainerBuilder $container)
    {
        $container->setParameter('trust_payments_payment.base_gateway_url', 'https://ep.trustpayments.com/');

        parent::build($container);
    }

    private function getModelClasses()
    {
        return [
            $this->container->get('models')->getClassMetadata(PaymentMethodConfiguration::class),
            $this->container->get('models')->getClassMetadata(TransactionInfo::class),
            $this->container->get('models')->getClassMetadata(OrderTransactionMapping::class)
        ];
    }

    private function updateSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $tool->updateSchema($this->getModelClasses(), true);
    }

    private function uninstallSchema()
    {
        $tool = new SchemaTool($this->container->get('models'));
        $tool->dropSchema($this->getModelClasses());
    }

    private function installWidgets(InstallContext $context)
    {
        $plugin = $context->getPlugin();
        $widget = new Widget();
        $widget->setName('trustPayments-payment-manual-tasks');
        $widget->setPlugin($plugin);
        $plugin->getWidgets()->add($widget);
    }

    private function uninstallWidgets(UninstallContext $context)
    {
        $plugin = $context->getPlugin();
        $widget = $plugin->getWidgets()->first();
        $this->container->get('models')->remove($widget);
        $this->container->get('models')->flush();
    }
}
