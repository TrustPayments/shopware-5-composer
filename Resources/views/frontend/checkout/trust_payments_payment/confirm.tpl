{#
/**
 * Trust Payments Shopware 5
 *
 * This Shopware 5 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
#}

{block name="frontend_index_header_javascript_jquery"}
	{$smarty.block.parent}
	<script type="text/javascript" src="{$trustPaymentsPaymentJavascriptUrl}"></script>
	<script type="text/javascript">
	var ShopwareTrustPaymentsCheckoutInit = function(){
		ShopwareTrustPayments.Checkout.init('trustpayments_payment_method_form', '{$trustPaymentsPaymentConfigurationId}', '{url controller='TrustPaymentsPaymentCheckout' action='saveOrder'}', '{$trustPaymentsPaymentPageUrl}');
	};
	{if $theme.asyncJavascriptLoading}
		if (typeof document.asyncReady == 'function') {
			document.asyncReady(function(){
				$(document).ready(ShopwareTrustPaymentsCheckoutInit);
			});
		} else {
			$(document).ready(ShopwareTrustPaymentsCheckoutInit);
		}
	{/if}
	</script>
{/block}

{block name="frontend_index_javascript_async_ready"}
	{$smarty.block.parent}
	{if !$theme.asyncJavascriptLoading}
		<script type="text/javascript">
			$(document).ready(ShopwareTrustPaymentsCheckoutInit);
		</script>
	{/if}
{/block}

{block name='frontend_checkout_confirm_premiums'}
	<div class="panel has--border" id="trustpayments_payment_method_form_container" style="position: absolute; left: -10000px;">
		<div class="panel--title is--underline">
			{s name="checkout/payment_information namespace=frontend/trustpayments_payment/main"}Payment Information{/s}
		</div>
		<div class="panel--body is--wide">
			<div id="trustpayments_payment_method_form"></div>
		</div>
	</div>
	{$smarty.block.parent}
{/block}

{block name="frontend_checkout_confirm_error_messages"}
	{$smarty.block.parent}
	{if $trustPaymentsPaymentFailureMessage}
		{include file="frontend/_includes/messages.tpl" type="error" content=$trustPaymentsPaymentFailureMessage}
	{/if}
	<div class="trustpayments-payment-validation-failure-message" style="display: none;">
		{include file="frontend/_includes/messages.tpl" type="error" content=""}
	</div>
{/block}
