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
//{block name="backend/order/model/order/fields"}
//{$smarty.block.parent}
	{ name: 'trustpayments_payment', type: 'boolean' },
//{/block}