<?php
declare(strict_types=1);

namespace ILogr\ShippingCountryNotice\Model\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;

/**
 * Adds a notice with a link under the Country field of the shipping address form.
 */
class LayoutProcessorPlugin
{
    /**
     * Template used by the Country field, resolved by Magento to view/frontend/web/template/form/.
     */
    public const TEMPLATE = 'ILogr_ShippingCountryNotice/form/field-html-notice';

    private const COUNTRY_FIELD_PATH = [
        'components', 'checkout', 'children', 'steps', 'children', 'shipping-step',
        'children', 'shippingAddress', 'children', 'shipping-address-fieldset',
        'children', 'country_id',
    ];

    /**
     * @param Escaper $escaper
     * @param UrlInterface $url
     */
    public function __construct(
        private readonly Escaper $escaper,
        private readonly UrlInterface $url
    ) {
    }

    /**
     * Set the notice and its template on the shipping Country field, if the checkout has that field.
     *
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        // Walk to the field without creating any missing node: a checkout without
        // this field (for example a customised one) is left exactly as it was.
        $field = &$jsLayout;
        foreach (self::COUNTRY_FIELD_PATH as $key) {
            if (!isset($field[$key]) || !is_array($field[$key])) {
                return $jsLayout;
            }
            $field = &$field[$key];
        }

        $link = sprintf(
            '<a href="%s">%s</a>',
            $this->escaper->escapeUrl($this->url->getUrl('contact')),
            $this->escaper->escapeHtml(__('contact us'))
        );

        $field['notice'] = (string)__(
            'Should you wish for your purchase to be shipped internationally, please %1.',
            $link
        );
        $field['config']['template'] = self::TEMPLATE;
        unset($field);

        return $jsLayout;
    }
}
