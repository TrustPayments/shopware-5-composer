/**
 * Trust Payments Shopware 5
 *
 * This Shopware 5 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/trustpayments_payment_refund/application"}
    //{include file="backend/trust_payments_payment_index/components/CTemplate.js"}
    //{include file="backend/trust_payments_payment_index/components/ComponentColumn.js"}

    Ext.define('Shopware.apps.TrustPaymentsPaymentRefund', {
        
        extend: 'Enlight.app.SubApplication',
        
        name: 'Shopware.apps.TrustPaymentsPaymentRefund',
        
        loadPath: '{url controller="TrustPaymentsPaymentRefund" action=load}',
        
        controllers: [
            'Main'
        ],
        
        launch: function() {
            var me = this,
                mainController = me.getController('Main');
            return mainController.mainWindow;
        }
        
    });
//{/block}