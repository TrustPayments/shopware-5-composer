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

namespace TrustPaymentsPayment\Components;

class Resource
{
    
    /**
     *
     * @var \TrustPaymentsPayment\Components\Provider\Language
     */
    private $languageProvider;

    /**
     *
     * @var string
     */
    private $baseGatewayUrl;

    /**
     * Constructor.
     *
     * @param \TrustPaymentsPayment\Components\Provider\Language $languageProvider
     * @param string $baseGatewayUrl
     */
    public function __construct(\TrustPaymentsPayment\Components\Provider\Language $languageProvider, $baseGatewayUrl)
    {
        $this->languageProvider = $languageProvider;
        $this->baseGatewayUrl = $baseGatewayUrl;
    }

    /**
     * Returns the URL to a resource on Trust Payments in the given context (space, space view, language).
     *
     * @param string $path
     * @param string $language
     * @param int $spaceId
     * @param int $spaceViewId
     * @return string
     */
    public function getResourceUrl($path, $language = null, $spaceId = null, $spaceViewId = null)
    {
        $url = $this->baseGatewayUrl;
        if (! empty($language) && $this->getLanguage($language)) {
            $url .= '/' . str_replace('_', '-', $language);
        }

        if (! empty($spaceId)) {
            $url .= '/s/' . $spaceId;
        }

        if (! empty($spaceViewId)) {
            $url .= '/' . $spaceViewId;
        }

        $url .= '/resource/' . $path;
        return $url;
    }
    
    private function getLanguage($shopLanguageCode)
    {
        if ($this->languageProvider->find($shopLanguageCode) !== false) {
            return $shopLanguageCode;
        } else {
            return null;
        }
    }
}
