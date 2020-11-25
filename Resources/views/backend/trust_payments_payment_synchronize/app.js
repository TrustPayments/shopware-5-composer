/**
 * Trust Payments Shopware 5
 *
 * This Shopware 5 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

//{block name="backend/trustpayments_payment_synchronize/application"}
Ext.define('Shopware.apps.TrustPaymentsPaymentSynchronize', {
    
    extend: 'Enlight.app.SubApplication',
    
    name: 'Shopware.apps.TrustPaymentsPaymentSynchronize',
    
    loadPath: '{url action=load}',
    
    controllers: [
        'Synchronize'
    ],
    
    launch: function() {
        var me = this;
        me.getController('Synchronize').directSynchronize();
    }
    
});
//{/block}