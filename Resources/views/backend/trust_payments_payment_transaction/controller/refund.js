/**
 * Trust Payments Shopware 5
 *
 * This Shopware 5 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{namespace name=backend/trustpayments_payment/main}
//{block name="backend/trustpayments_payment_transaction/controller/refund"}
Ext.define('Shopware.apps.TrustPaymentsPaymentTransaction.controller.Refund', {

    extend:'Ext.app.Controller',

    refs: [
        { ref: 'refundGrid', selector: 'trustpayments-payment-transaction-refunds-grid' }
    ],

    snippets: {
        message: {
            successful: '{s name="refund/message/marked_as_successful"}The refund has been marked as successful.{/s}',
            failed: '{s name="refund/message/marked_as_failed"}The refund has been marked as failed.{/s}',
            exception: '{s name="message/error"}There has been an exception.{/s}'
        },
        growlTitle: '{s name="growl_title"}Trust Payments Payment{/s}'
    },

    init:function () {
        var me = this;

        me.control({
            'trustpayments-payment-transaction-refunds': {
                createRefund: me.onCreateRefund
            },
            'trustpayments-payment-transaction-refunds-grid': {
                showDetail: me.onShowRefundDetail
            },
            'trustpayments-payment-transaction-refunds-details': {
                markAsFailed: me.onMarkAsFailed,
                markAsSuccessful: me.onMarkAsSuccessful,
                downloadRefund: me.onDownloadRefund
            }
        });

        me.callParent(arguments);
    },
    
    onCreateRefund: function(record){
        var me = this;
        
        Shopware.app.Application.addSubApplication({
            name: 'Shopware.apps.TrustPaymentsPaymentRefund',
            eventScope: me,
            record: record
        });
    },
    
    onShowRefundDetail: function(record, grid) {
        var me = this;
        grid.view.up().next().updateRecord(record);
    },
    
    onDownloadRefund: function(record, transaction, view) {
        window.open('{url controller="TrustPaymentsPaymentRefund" action=downloadRefund}?id=' + transaction.get('id') + '&refundId=' + record.get('id'), 'download');
    },
    
    onMarkAsFailed: function(record, transaction, view) {
        var me = this;
        
        if (record.get('state') == 'MANUAL_CHECK') {
            me.process(record, transaction, view, '{url controller="TrustPaymentsPaymentRefund" action="markAsFailed"}',
                    me.snippets.message.failed, me.snippets.message.exception);
        }
    },
    
    onMarkAsSuccessful: function(record, transaction, view) {
        var me = this;
        
        if (record.get('state') == 'MANUAL_CHECK') {
            me.process(record, transaction, view, '{url controller="TrustPaymentsPaymentRefund" action="markAsSuccessful"}',
                    me.snippets.message.successful, me.snippets.message.exception);
        }
    },
    
    process: function(record, transaction, view, url, successText, failureText){
        var me = this,
            store = me.subApplication.getStore('Shopware.apps.TrustPaymentsPaymentTransaction.store.Transaction'),
            window = view.up('window');
        
        window.setLoading(true);
        Ext.Ajax.request({
            url: url,
            params: {
                spaceId: transaction.get('spaceId'),
                refundId: record.get('id')
            },
            success: function(response) {
                var data = Ext.decode(response.responseText),
                    text;
                if (data.success) {
                    store.load({
                        params: {
                            transactionId: transaction.get('id')
                        },
                        callback:function (records) {
                            window.updateRecord(records[0]);
                            window.setLoading(false);
                            me.getRefundGrid().getSelectionModel().select(records[0].getRefunds().getById(record.get('id')));
                        }
                    });
                    text = successText;
                } else {
                    window.setLoading(false);
                    text = failureText;
                }
                Shopware.Notification.createGrowlMessage(me.snippets.growlTitle, text);
            }
        });
    }
    
});
//{/block}