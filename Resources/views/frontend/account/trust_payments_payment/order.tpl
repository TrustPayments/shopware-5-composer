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

{block name='frontend_account_order_item_repeat_order'}
	{if $offerPosition.trustPaymentsTransaction && ($offerPosition.trustPaymentsTransaction.canDownloadInvoice || $offerPosition.trustPaymentsTransaction.canDownloadPackingSlip)}
		<div class="panel--tr is--odd">
			<div class="panel--td">
				{if $offerPosition.trustPaymentsTransaction.canDownloadInvoice}
					<a href="{url controller='TrustPaymentsPaymentTransaction' action='downloadInvoice' id=$offerPosition.trustPaymentsTransaction.id}" title="{s name="account/button/download_invoice" namespace="frontend/trustpayments_payment/main"}Download Invoice{/s}" class="btn is--small">
						{s name="account/button/download_invoice" namespace="frontend/trustpayments_payment/main"}Download Invoice{/s}
					</a>
				{/if}
				{if $offerPosition.trustPaymentsTransaction.canDownloadPackingSlip}
					<a href="{url controller='TrustPaymentsPaymentTransaction' action='downloadPackingSlip' id=$offerPosition.trustPaymentsTransaction.id}" title="{s name="account/button/download_packing_slip" namespace="frontend/trustpayments_payment/main"}Download Packing Slip{/s}" class="btn is--small">
						{s name="account/button/download_packing_slip" namespace="frontend/trustpayments_payment/main"}Download Packing Slip{/s}
					</a>
				{/if}
			</div>
		</div>
	{/if}
	{if $offerPosition.trustPaymentsTransaction.refunds  && $offerPosition.trustPaymentsTransaction.canDownloadRefunds}
		<div class="panel--tr is--odd">
			<div class="panel--td column--name">
				<p class="is--strong">{s name="account/header/refunds" namespace="frontend/trustpayments_payment/main"}Refunds{/s}</p>
				{foreach $offerPosition.trustPaymentsTransaction.refunds as $refund}
					<p>
                        {$refund.date|date}
					</p>
				{/foreach}
			</div>
			<div class="panel--td column--price">
				<p>&nbsp;</p>
				{foreach $offerPosition.trustPaymentsTransaction.refunds as $refund}
					<p>
						{if $offerPosition.currency_position == "32"}
                            {$offerPosition.currency_html} {$refund.amount}
                        {else}
                            {$refund.amount} {$offerPosition.currency_html}
                        {/if}
					</p>
				{/foreach}
			</div>
			<div class="panel--td column--total">
				<p>&nbsp;</p>
				{foreach $offerPosition.trustPaymentsTransaction.refunds as $refund}
					<p>
						{if $refund.canDownload}
                        	<a href="{url controller='TrustPaymentsPaymentTransaction' action='downloadRefund' id=$offerPosition.trustPaymentsTransaction.id refund=$refund.id}" title="{s name="account/button/download" namespace="frontend/trustpayments_payment/main"}Download{/s}">
								{s name="account/button/download" namespace="frontend/trustpayments_payment/main"}Download{/s}
							</a>
                        {/if}
					</p>
				{/foreach}
			</div>
		</div>
	{/if}
	{$smarty.block.parent}
{/block}