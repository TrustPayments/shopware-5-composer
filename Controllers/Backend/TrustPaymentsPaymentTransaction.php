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

use TrustPaymentsPayment\Models\TransactionInfo;
use Shopware\Components\CSRFWhitelistAware;
use TrustPaymentsPayment\Models\OrderTransactionMapping;
use TrustPaymentsPayment\Components\Controller\Backend;
use TrustPaymentsPayment\Components\ArrayBuilder\LineItemVersion as LineItemVersionBuilder;

class Shopware_Controllers_Backend_TrustPaymentsPaymentTransaction extends Backend implements CSRFWhitelistAware
{
    public function getWhitelistedCSRFActions()
    {
        return [
            'downloadInvoice',
            'downloadPackingSlip'
        ];
    }

    public function saveLineItemAction()
    {
        $spaceId = $this->Request()->getParam('spaceId', null);
        if (empty($spaceId)) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()
                    ->getParams(),
                'message' => $this->get('snippets')->getNamespace('backend/trustpayments_payment/main')->get('error/no_space_id_passed', 'No valid space id passed.')
            ));
            return;
        }

        $transactionId = $this->Request()->getParam('transactionId', null);
        if (empty($transactionId)) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()
                    ->getParams(),
                'message' => $this->get('snippets')->getNamespace('backend/trustpayments_payment/main')->get('error/no_transaction_id_passed', 'No valid transaction id passed.')
            ));
            return;
        }

        try {
            /* @var \TrustPayments\Sdk\Model\Transaction $transaction */
            $transaction = $this->get('trustpayments_payment.transaction')->getTransaction($spaceId, $transactionId);
            /* @var \TrustPayments\Sdk\Model\TransactionLineItemVersion $lineItemVersion */
            $lineItemVersion = $this->get('trustpayments_payment.transaction')->getLineItemVersion($spaceId, $transactionId);
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()
                    ->getParams(),
                'message' => $this->get('snippets')->getNamespace('backend/trustpayments_payment/main')->get('error/cannot_load_transaction', 'The transaction cannot be loaded.')
            ));
            return;
        }

        $lineItems = $this->Request()->getParam('lineItems');

        $updatedLineItems = [];
        foreach ($lineItemVersion->getLineItems() as $existingLineItem) {
            if (! isset($lineItems[$existingLineItem->getUniqueId()])) {
                $updatedLineItems[] = $existingLineItem;
            } else {
                $lineItem = new \TrustPayments\Sdk\Model\LineItemCreate();
                $lineItem->setAmountIncludingTax($lineItems[$existingLineItem->getUniqueId()]['amount']);
                $lineItem->setQuantity($lineItems[$existingLineItem->getUniqueId()]['quantity']);
                $lineItem->setName($existingLineItem->getName());
                $lineItem->setShippingRequired($existingLineItem->getShippingRequired());
                $lineItem->setSku($existingLineItem->getSku());
                $lineItem->setTaxes($existingLineItem->getTaxes());
                $lineItem->setType($existingLineItem->getType());
                $lineItem->setUniqueId($existingLineItem->getUniqueId());
                $updatedLineItems[] = $lineItem;
            }
        }

        /* @var \TrustPayments\Sdk\Model\TransactionLineItemVersion $updatedLineItemVersion */
        $updatedLineItemVersion = $this->get('trustpayments_payment.transaction')->updateLineItems($spaceId, $transactionId, $updatedLineItems);
        $lineItemVersionBuilder = new LineItemVersionBuilder($this->container, $updatedLineItemVersion);

        $this->View()->assign(array(
            'success' => true,
            'data' => $lineItemVersionBuilder->build(),
            'lineItemTotalAmount' => $updatedLineItemVersion->getAmount()
        ));
    }

    public function voidAction()
    {
        $id = $this->Request()->getParam('id');
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->find($id);

        try {
            $voidService = new \TrustPayments\Sdk\Service\TransactionVoidService($this->get('trustpayments_payment.api_client')->getInstance());
            $voidService->voidOnline($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            $this->get('trustpayments_payment.transaction')->handleTransactionState($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false
            ));
        }
    }

    public function completeAction()
    {
        $id = $this->Request()->getParam('id');
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->find($id);

        try {
            $completionService = new \TrustPayments\Sdk\Service\TransactionCompletionService($this->get('trustpayments_payment.api_client')->getInstance());
            $completionService->completeOnline($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            $this->get('trustpayments_payment.transaction')->handleTransactionState($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false
            ));
        }
    }

    public function acceptAction()
    {
        $id = $this->Request()->getParam('id');
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->find($id);

        try {
            $this->get('trustpayments_payment.delivery_indication')->accept($transactionInfo);
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false
            ));
        }
    }

    public function denyAction()
    {
        $id = $this->Request()->getParam('id');
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->find($id);

        try {
            $this->get('trustpayments_payment.delivery_indication')->deny($transactionInfo);
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false
            ));
        }
    }
    
    public function updateAction()
    {
        $id = $this->Request()->getParam('id');
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->find($id);
        
        try {
            $this->get('trustpayments_payment.payment')->fetchPaymentStatus($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            $this->View()->assign(array(
                'success' => true
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false
            ));
        }
    }

    public function downloadInvoiceAction()
    {
        $id = $this->Request()->getParam('id');
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->find($id);

        $service = new \TrustPayments\Sdk\Service\TransactionService($this->get('trustpayments_payment.api_client')->getInstance());
        $document = $service->getInvoiceDocument($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
        $this->download($document);
    }

    public function downloadPackingSlipAction()
    {
        $id = $this->Request()->getParam('id');
        /* @var TransactionInfo $transactionInfo */
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->find($id);

        $service = new \TrustPayments\Sdk\Service\TransactionService($this->get('trustpayments_payment.api_client')->getInstance());
        $document = $service->getPackingSlip($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
        $this->download($document);
    }

    public function getTransactionsAction()
    {
        $transactionId = $this->Request()->getParam('transactionId');
        $orderId = $this->Request()->getParam('orderId');
        if (! empty($transactionId)) {
            $transactionInfos = [
                $this->getModelManager()
                    ->getRepository(TransactionInfo::class)
                    ->find($transactionId)
            ];
        } elseif (! empty($orderId)) {
            $transactionInfos = [
                $this->getTransactionInfoByOrder($orderId)
            ];
        } else {
            $transactionInfos = $this->getModelManager()
                ->getRepository(TransactionInfo::class)
                ->findAll();
        }

        /* @var \TrustPaymentsPayment\Components\TransactionInfo $transactionInfoService */
        $transactionInfoService= $this->get('trustpayments_payment.transaction_info');
        $items = array();
        foreach ($transactionInfos as $transactionInfo) {
            if ($transactionInfo instanceof TransactionInfo) {
                $items[] = $transactionInfoService->buildTransactionInfoAsArray($transactionInfo);
            }
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $items,
            'count' => count($items)
        ));
    }

    private function getTransactionInfoByOrder($orderId)
    {
        $transactionInfo = $this->getModelManager()
            ->getRepository(TransactionInfo::class)
            ->findOneBy([
            'orderId' => $orderId
        ]);
        if ($transactionInfo instanceof TransactionInfo) {
            return $transactionInfo;
        }

        $mapping = $this->getModelManager()
            ->getRepository(OrderTransactionMapping::class)
            ->findOneBy([
            'orderId' => $orderId
        ]);
        if ($mapping instanceof OrderTransactionMapping) {
            return $this->get('trustpayments_payment.transaction_info')->updateTransactionInfoByOrder($this->get('trustpayments_payment.transaction')
                ->getTransaction($mapping->getSpaceId(), $mapping->getTransactionId()), $mapping->getOrder());
        }
    }
}
