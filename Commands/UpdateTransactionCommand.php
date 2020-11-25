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

namespace TrustPaymentsPayment\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TrustPaymentsPayment\Models\TransactionInfo as TransactionInfoModel;
use TrustPayments\Sdk\Model\TransactionInvoice;
use TrustPayments\Sdk\Model\Transaction as TransactionModel;

class UpdateTransactionCommand extends ShopwareCommand
{
    protected function configure()
    {
        $this
            ->setName('trustpayments:transaction:update')
            ->setDescription('Trust Payments: Update transactions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        
        if (Shopware()->Front()->Request() == null) {
            Shopware()->Front()->setRequest(new \Enlight_Controller_Request_RequestHttp());
        }
        
        /* @var \TrustPaymentsPayment\Components\Transaction $transactionService */
        $transactionService = $this->getContainer()->get('trustpayments_payment.transaction');
        /* @var \TrustPaymentsPayment\Components\Invoice $invoiceService */
        $invoiceService = $this->getContainer()->get('trustpayments_payment.invoice');
        /* @var \TrustPaymentsPayment\Components\TransactionInfo $transactionInfoService */
        $transactionInfoService = $this->getContainer()->get('trustpayments_payment.transaction_info');
        /* @var \TrustPaymentsPayment\Subscriber\Webhook\Transaction $transactionWebhookService */
        $transactionWebhookService = $this->getContainer()->get('trustpayments_payment.subscriber.webhook.transaction');
        /* @var \TrustPaymentsPayment\Subscriber\Webhook\TransactionInvoice $invoiceWebhookService */
        $invoiceWebhookService = $this->getContainer()->get('trustpayments_payment.subscriber.webhook.transaction_invoice');
        
        // Can only update one transaction at a time, because the context changes.
        $transactionInfos = $this->getContainer()->get('models')->getRepository(TransactionInfoModel::class)->findBy([
            'state' => 'CONFIRMED'
        ], null, 1, null);
        foreach ($transactionInfos as $transactionInfo) {
            /* @var TransactionInfoModel $transactionInfo */
            $this->getContainer()->set('shop', $transactionInfo->getShop());
            $transaction = $transactionService->getTransaction($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            if ($transaction instanceof TransactionModel) {
                $transactionWebhookService->process($transaction);
            
                $invoice = $invoiceService->getInvoice($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
                if ($invoice instanceof TransactionInvoice) {
                    $invoiceWebhookService->process($invoice);
                }
            
                $output->writeln('Updated transaction ' . $transaction->getId() . '.');
            }
        }
        
        return 0;
    }
}
