/**
 * Trust Payments Shopware 5
 *
 * This Shopware 5 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/trustpayments_payment_transaction/model/transaction"}
Ext.define('Shopware.apps.TrustPaymentsPaymentTransaction.model.Transaction', {
    
    extend: 'Ext.data.Model',
    
    fields: [
        //{block name="backend/trustpayments_payment_transaction/model/transaction/fields"}{/block}
        { name: 'id', type: 'int' },
        { name: 'transactionId', type: 'int' },
        { name: 'orderId', type: 'int' },
        { name: 'shopId', type: 'int' },
        { name: 'state', type: 'string' },
        { name: 'spaceId', type: 'int' },
        { name: 'spaceViewId', type: 'int' },
        { name: 'language', type: 'string' },
        { name: 'currency', type: 'string' },
        { name: 'createdAt', type: 'date' },
        { name: 'authorizationAmount', type: 'string' },
        { name: 'image', type: 'string' },
        { name: 'labels', type: 'object' },
        { name: 'failureReason', type: 'string' },
        { name: 'paymentMethod', type: 'object' },
        { name: 'transactionUrl', type: 'string' },
        { name: 'customerUrl', type: 'string' },
        { name: 'currencyDecimals', type: 'int' },
        { name: 'lineItemTotalAmount', type: 'float' },
        { name: 'canDownloadInvoice', type: 'boolean' },
        { name: 'canDownloadPackingSlip', type: 'boolean' },
        { name: 'canReview', type: 'boolean' },
        { name: 'canVoid', type: 'boolean' },
        { name: 'canComplete', type: 'boolean' },
        { name: 'canUpdateLineItems', type: 'boolean' },
        { name: 'canRefund', type: 'boolean' }
    ],

    associations:[
        {
            type: 'hasMany',
            model: 'Shopware.apps.TrustPaymentsPaymentTransaction.model.LineItem',
            name: 'getLineItems',
            associationKey: 'lineItems'
        },
        {
            type: 'hasMany',
            model: 'Shopware.apps.TrustPaymentsPaymentTransaction.model.RefundLineItem',
            name: 'getRefundBaseLineItems',
            associationKey: 'refundBaseLineItems'
        },
        {
            type: 'hasMany',
            model: 'Shopware.apps.TrustPaymentsPaymentTransaction.model.Refund',
            name: 'getRefunds',
            associationKey: 'refunds',
            storeConfig: {
                sorters: [{
                    property : 'createdOn',
                    direction: 'DESC'
                }]
            }
        },
        {
            type: 'hasMany',
            model: 'Shopware.apps.Base.model.Shop',
            name: 'getShop',
            associationKey: 'shop'
        }
    ],
    
    proxy: {
        type: 'ajax',
 
        api: {
            read: '{url controller="TrustPaymentsPaymentTransaction" action="getTransactions"}'
        },
 
        reader: {
            type:			'json',
            root:			'data',
            totalProperty:	'total'
        }
    }

});
//{/block}