/**
 * Trust Payments Shopware 5
 *
 * This Shopware 5 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/trustpayments_payment_transaction/model/refund_line_item"}
Ext.define('Shopware.apps.TrustPaymentsPaymentTransaction.model.RefundLineItem', {
    
    extend: 'Ext.data.Model',
    
    quantityReduction: 0,
    unitPriceReduction: 0,
 
    fields: [
        //{block name="backend/trustpayments_payment_transaction/model/refund_line_item/fields"}{/block}
        { name: 'id', type: 'string' },
        { name: 'uniqueId', type: 'string' },
        { name: 'sku', type: 'string' },
        { name: 'name', type: 'string' },
        { name: 'amountIncludingTax', type: 'float' },
        { name: 'unitPriceIncludingTax', type: 'float' },
        { name: 'taxes', type: 'object' },
        { name: 'taxRate', type: 'float' },
        { name: 'type', type: 'string' },
        { name: 'quantity', type: 'float' },
        { name: 'shippingRequired', type: 'boolean' }
    ]

});
//{/block}