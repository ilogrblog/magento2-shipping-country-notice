<?php
declare(strict_types=1);

namespace ILogr\ShippingCountryNotice\Test\Unit\Model\Checkout;

use ILogr\ShippingCountryNotice\Model\Checkout\LayoutProcessorPlugin;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\Escaper;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class LayoutProcessorPluginTest extends TestCase
{
    /**
     * @var LayoutProcessor
     */
    private LayoutProcessor $subject;

    protected function setUp(): void
    {
        $this->subject = $this->createStub(LayoutProcessor::class);
    }

    private function pluginWithUrl(string $contactUrl): LayoutProcessorPlugin
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturn($contactUrl);

        return new LayoutProcessorPlugin($this->escaper(), $url);
    }

    /**
     * The real Escaper, so the tests check real escaping. Its inline translator is normally fetched
     * from the object manager, which a unit test does not have, so a no-op one is set instead.
     */
    private function escaper(): Escaper
    {
        $escaper = new Escaper();
        $property = new \ReflectionProperty(Escaper::class, 'translateInline');
        $property->setValue($escaper, $this->createStub(InlineInterface::class));

        return $escaper;
    }

    private function layout(array $fields): array
    {
        return ['components' => ['checkout' => ['children' => ['steps' => ['children' => [
            'shipping-step' => ['children' => ['shippingAddress' => ['children' => [
                'shipping-address-fieldset' => ['children' => $fields],
            ]]]],
        ]]]]]];
    }

    private function fields(array $layout): array
    {
        return $layout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
    }

    public function testCountryFieldGetsLinkNoticeAndTemplate(): void
    {
        $url = $this->createMock(UrlInterface::class);
        $url->expects($this->once())->method('getUrl')->with('contact')
            ->willReturn('https://shop.example.test/contact/');
        $plugin = new LayoutProcessorPlugin($this->escaper(), $url);
        $input = $this->layout([
            'country_id' => ['config' => ['template' => 'ui/form/field', 'elementTmpl' => 'ui/form/element/select']],
        ]);

        $country = $this->fields($plugin->afterProcess($this->subject, $input))['country_id'];

        $this->assertSame(
            'Should you wish for your purchase to be shipped internationally, please '
            . '<a href="https://shop.example.test/contact/">contact us</a>.',
            $country['notice']
        );
        $this->assertSame('ILogr_ShippingCountryNotice/form/field-html-notice', $country['config']['template']);
        $this->assertSame('ui/form/element/select', $country['config']['elementTmpl'], 'other config is kept');
    }

    public function testOtherFieldsAreNotTouched(): void
    {
        $plugin = $this->pluginWithUrl('https://shop.example.test/contact/');
        $telephone = ['config' => ['template' => 'ui/form/field'], 'notice' => 'Plain <b>text</b>'];
        $input = $this->layout(['country_id' => [], 'telephone' => $telephone]);

        $fields = $this->fields($plugin->afterProcess($this->subject, $input));

        $this->assertSame($telephone, $fields['telephone']);
    }

    public function testUrlIsEscapedBeforeItGoesIntoTheLink(): void
    {
        $plugin = $this->pluginWithUrl('javascript:alert(1)"onmouseover="alert(2)');
        $input = $this->layout(['country_id' => []]);

        $notice = $this->fields($plugin->afterProcess($this->subject, $input))['country_id']['notice'];

        $this->assertStringNotContainsString('javascript:', $notice);
        $this->assertStringNotContainsString('"onmouseover', $notice);
        $this->assertSame(1, substr_count($notice, '<a '), 'exactly one tag opened by the module');
        $this->assertSame(2, substr_count($notice, '"'), 'the href value has no stray quote');
    }

    public function testMissingCountryFieldCreatesNothing(): void
    {
        $url = $this->createMock(UrlInterface::class);
        $url->expects($this->never())->method('getUrl');
        $plugin = new LayoutProcessorPlugin($this->escaper(), $url);
        $input = $this->layout(['telephone' => []]);

        $this->assertSame($input, $plugin->afterProcess($this->subject, $input));
    }

    public function testCheckoutWithoutShippingStepIsReturnedUnchanged(): void
    {
        $plugin = $this->pluginWithUrl('https://shop.example.test/contact/');
        foreach ([[], ['components' => ['checkout' => ['children' => []]]]] as $input) {
            $this->assertSame($input, $plugin->afterProcess($this->subject, $input));
        }
    }

    public function testTemplateFileExistsAtTheResolvedPath(): void
    {
        $module = dirname(__DIR__, 4);
        $prefix = 'ILogr_ShippingCountryNotice/';
        $this->assertStringStartsWith($prefix, LayoutProcessorPlugin::TEMPLATE);
        $name = substr(LayoutProcessorPlugin::TEMPLATE, strlen($prefix));
        $file = $module . '/view/frontend/web/template/' . $name . '.html';

        $this->assertFileExists($file);
        $html = (string)file_get_contents($file);
        $this->assertStringContainsString('data-bind="html: element.notice"', $html);
        $this->assertStringNotContainsString('text: element.notice', $html);
        $this->assertSame(
            substr_count($html, '<!-- ko '),
            substr_count($html, '<!-- /ko -->'),
            'every opening ko comment has a closing one'
        );
    }
}
