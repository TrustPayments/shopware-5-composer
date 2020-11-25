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

namespace TrustPaymentsPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;
use TrustPaymentsPayment\Components\Transaction as TransactionService;
use TrustPaymentsPayment\Components\Session as SessionService;
use TrustPaymentsPayment\Models\PaymentMethodConfiguration as PaymentMethodConfigurationModel;
use TrustPaymentsPayment\Models\TransactionInfo;
use Shopware\Models\Payment\Payment;

class Checkout implements SubscriberInterface
{

    /**
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     *
     * @var TransactionService
     */
    private $transactionService;

    /**
     *
     * @var SessionService
     */
    private $sessionService;

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Frontend_Checkout::preDispatch::after' => 'onPreDispatch',
            'Shopware_Controllers_Frontend_Checkout::cartAction::after' => 'onCartAction',
            'Shopware_Controllers_Frontend_Checkout::confirmAction::after' => 'onConfirmAction',
            'Shopware_Controllers_Frontend_Checkout::getMinimumCharge::after' => 'onGetMinimumCharge'
        ];
    }

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param ModelManager $modelManager
     * @param TransactionService $transactionService
     * @param SessionService $sessionService
     */
    public function __construct(ContainerInterface $container, ModelManager $modelManager, TransactionService $transactionService, SessionService $sessionService)
    {
        $this->container = $container;
        $this->modelManager = $modelManager;
        $this->transactionService = $transactionService;
        $this->sessionService = $sessionService;
    }
    
    public function onPreDispatch(\Enlight_Hook_HookArgs $args)
    {
        /* @var \Shopware_Controllers_Frontend_Checkout $checkoutController */
        $checkoutController = $args->getSubject();
        
        if ($checkoutController->Request()->getActionName() != 'finish') {
            /* @var \Enlight_Components_Session_Namespace $session */
            $session = $this->container->get('session');
            $session['trustpayments_payment.success'] = false;
        }
    }
    
    public function onCartAction(\Enlight_Hook_HookArgs $args)
    {
        /* @var \Shopware_Controllers_Frontend_Checkout $checkoutController */
        $checkoutController = $args->getSubject();
        $view = $checkoutController->View();
        $view->addTemplateDir($this->container->getParameter('trust_payments_payment.plugin_dir') . '/Resources/views/');
        $view->extendsTemplate('frontend/checkout/trust_payments_payment/device-javascript.tpl');
        $view->assign('trustPaymentsPaymentDeviceJavascriptUrl', $this->transactionService->getDeviceJavascriptUrl());
    }

    public function onConfirmAction(\Enlight_Hook_HookArgs $args)
    {
        /* @var \Shopware_Controllers_Frontend_Checkout $checkoutController */
        $checkoutController = $args->getSubject();

        $view = $checkoutController->View();
        $view->addTemplateDir($this->container->getParameter('trust_payments_payment.plugin_dir') . '/Resources/views/');
        $view->extendsTemplate('frontend/checkout/trust_payments_payment/device-javascript.tpl');
        $view->assign('trustPaymentsPaymentDeviceJavascriptUrl', $this->transactionService->getDeviceJavascriptUrl());
        
        if (empty($view->sUserLoggedIn)) {
            // When the customer is not logged in, we don't do anything.
            return;
        }

        $paymentData = $checkoutController->getSelectedPayment();
        if ($paymentData != false && isset($paymentData['id'])) {
            /* @var Payment $payment */
            $payment = $this->modelManager->getRepository(Payment::class)->find($paymentData['id']);
            /* @var Plugin $plugin */
            $plugin = $this->modelManager->getRepository(Plugin::class)->findOneBy([
                'name' => $this->container->getParameter('trust_payments_payment.plugin_name')
            ]);
            if ($payment instanceof \Shopware\Models\Payment\Payment && $plugin->getId() == $payment->getPluginId()) {
                $paymentMethodConfiguration = $this->modelManager->getRepository(PaymentMethodConfigurationModel::class)->findOneBy([
                    'paymentId' => $payment->getId()
                ]);
                if ($paymentMethodConfiguration instanceof PaymentMethodConfigurationModel) {
                    $view->addTemplateDir($this->container->getParameter('trust_payments_payment.plugin_dir') . '/Resources/views/');
                    $view->extendsTemplate('frontend/checkout/trust_payments_payment/confirm.tpl');

                    $view->assign('trustPaymentsPaymentJavascriptUrl', $this->transactionService->getJavaScriptUrl());
                    $view->assign('trustPaymentsPaymentPageUrl', $this->transactionService->getPaymentPageUrl());
                    $view->assign('trustPaymentsPaymentConfigurationId', $paymentMethodConfiguration->getConfigurationId());

                    $userFailureMessage = $this->getUserFailureMessage();
                    if (!empty($userFailureMessage)) {
                        $view->assign('trustPaymentsPaymentFailureMessage', $userFailureMessage);
                    }
                }
            }
        }

        $trustPaymentsErrors = $checkoutController->Request()->getParam('trustPaymentsErrors');
        if (!empty($trustPaymentsErrors)) {
            $view->assign('trustPaymentsPaymentFailureMessage', $trustPaymentsErrors);
        }
    }

    private function getUserFailureMessage()
    {
        /* @var \Enlight_Components_Session_Namespace $session */
        $session = $this->container->get('session');
        if (isset($session['trustpayments_payment.transaction_timeout']) && !empty($session['trustpayments_payment.transaction_timeout'])) {
            $session['trustpayments_payment.transaction_timeout'] = '';
            $namespace = $this->container->get('snippets')->getNamespace('frontend/trustpayments_payment/main');
            return $namespace->get('checkout/transaction_timeout', 'The payment timed out. Please try again.');
        }
        if (isset($session['trustpayments_payment.failed_transaction']) && !empty($session['trustpayments_payment.failed_transaction'])) {
            /* @var TransactionInfo $transactionInfo */
            $transactionInfo = $this->modelManager
                ->getRepository(TransactionInfo::class)
                ->find($session['trustpayments_payment.failed_transaction']);
            $session['trustpayments_payment.failed_transaction'] = '';

            if ($transactionInfo instanceof TransactionInfo) {
                return $transactionInfo->getUserFailureMessage();
            }
        }
        return null;
    }
    
    public function onGetMinimumCharge(\Enlight_Hook_HookArgs $args)
    {
        /* @var \Enlight_Components_Session_Namespace $session */
        $session = $this->container->get('session');
        if ($session['trustpayments_payment.success'] === true) {
            $args->setReturn(false);
        }
        return $args->getReturn();
    }
}
