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

namespace TrustPaymentsPayment\Components\ArrayBuilder;

use TrustPayments\Sdk\Model\Refund as RefundModel;
use TrustPaymentsPayment\Components\ArrayBuilder\LineItem as LineItemArrayBuilder;
use TrustPaymentsPayment\Components\ArrayBuilder\Label as LabelArrayBuilder;
use TrustPaymentsPayment\Components\ArrayBuilder\LabelGroup as LabelGroupArrayBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TrustPayments\Sdk\Model\TransactionInvoice;

class Refund extends AbstractArrayBuilder
{
    /**
     *
     * @var RefundModel
     */
    private $refund;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param RefundModel $refund
     */
    public function __construct(ContainerInterface $container, RefundModel $refund)
    {
        parent::__construct($container);
        $this->refund = $refund;
    }

    public function build()
    {
        return [
            'id' => $this->refund->getId(),
            'state' => $this->refund->getState(),
            'createdOn' => $this->refund->getCreatedOn(),
            'amount' => $this->refund->getAmount(),
            'externalId' => $this->refund->getExternalId(),
            'failureReason' => $this->refund->getFailureReason() != null ? $this->translate($this->refund->getFailureReason()
                ->getDescription()) : null,
            'labels' => LabelGroupArrayBuilder::buildGrouped($this->container, $this->getLabelBuilders()),
            'lineItems' => $this->getLineItems()
        ];
    }

    /**
     *
     * @param ContainerInterface $container
     * @param TransactionInvoice $invoice
     * @param Refund[] $refunds
     * @return array
     */
    public static function buildBaseLineItems(ContainerInterface $container, TransactionInvoice $invoice = null, array $refunds = [])
    {
        /* @var \TrustPaymentsPayment\Components\Refund $refundService */
        $refundService = $container->get('trustpayments_payment.refund');

        $result = [];
        foreach ($refundService->getRefundBaseLineItems($invoice, $refunds) as $lineItem) {
            $lineItemBuilder = new LineItemArrayBuilder($container, $lineItem);
            $result[] = $lineItemBuilder->build();
        }
        return $result;
    }

    /**
     *
     * @return LabelArrayBuilder[]
     */
    private function getLabelBuilders()
    {
        /** @var \TrustPaymentsPayment\Components\Provider\LabelDescriptorProvider $labelDescriptorProvider */
        $labelDescriptorProvider = $this->container->get('trustpayments_payment.provider.label_descriptor');

        $labels = [];
        try {
            foreach ($this->refund->getLabels() as $label) {
                $labels[] = new LabelArrayBuilder($this->container, $label->getDescriptor(), $label->getContentAsString());
            }
        } catch (\Exception $e) {
            // If label descriptors and label descriptor groups cannot be loaded from Trust Payments, the labels cannot be displayed.
        }
        return $labels;
    }

    /**
     *
     * @return LineItemArrayBuilder[]
     */
    private function getLineItems()
    {
        $lineItems = [];
        foreach ($this->refund->getLineItems() as $lineItem) {
            $lineItemBuilder = new LineItemArrayBuilder($this->container, $lineItem);
            $lineItems[] = $lineItemBuilder->build();
        }
        return $lineItems;
    }
}
