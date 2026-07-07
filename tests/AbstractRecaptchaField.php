<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use ReflectionMethod;
use YiiRocks\Recaptcha\Exception\MissingSiteKeyException;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Result;

abstract class AbstractRecaptchaField extends TestCase
{
    protected function setUp(): void
    {
        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
        );
    }

    protected function tearDown(): void
    {
        RecaptchaRegistry::reset();
    }

    public function testGenerateInputThrowsWhenSiteKeyNotSet(): void
    {
        $field = $this->field('captcha');

        $method = new ReflectionMethod($field, 'generateInput');

        $this->expectException(MissingSiteKeyException::class);

        $method->invoke($field);
    }

    public function testMultipleWidgetsHaveUniqueIds(): void
    {
        $html1 = $this->field('captcha')->withSiteKey('key1')->render();
        $html2 = $this->field('captcha')->withSiteKey('key2')->render();

        preg_match('/id="([^"]+)"/', $html1, $m1);
        preg_match('/id="([^"]+)"/', $html2, $m2);

        $this->assertNotNull($m1[1] ?? null);
        $this->assertNotNull($m2[1] ?? null);
        $this->assertNotSame($m1[1], $m2[1]);
    }

    public function testPerFieldOverrideTakesPrecedenceOverRegistryDefaults(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'attributes' => ['class' => 'global-class'],
        ]);

        $html = $this->field('captcha')
            ->withSiteKey('test-key')
            ->containerAttributes(['class' => 'per-field'])
            ->render();

        $this->assertStringStartsWith('<div class="per-field">', $html);
    }

    public function testRegistryContainerDefaultsApplied(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'attributes' => ['class' => 'global-class'],
        ]);

        $html = $this->field('captcha')->withSiteKey('test-key')->render();

        $this->assertStringStartsWith('<div class="global-class">' . "\n", $html);
    }

    public function testRegistryContainerTagOverridesDefaultDiv(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'tag' => 'section',
        ]);

        $html = $this->field('captcha')->withSiteKey('test-key')->render();

        $this->assertStringStartsWith('<section class="mb-3">', $html);
    }

    public function testRegistryUseContainerFalseDisablesContainer(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'useContainer' => false,
        ]);

        $html = $this->field('captcha')->withSiteKey('test-key')->render();

        $this->assertStringNotContainsString('mb-3', $html);
        $this->assertStringStartsNotWith('<div class="mb-3">', $html);
    }

    public function testRenderWithContainerClass(): void
    {
        $html = $this->field('captcha')
            ->withSiteKey('test-key')
            ->containerClass('form-group', 'custom')
            ->render();

        $this->assertStringStartsWith('<div class="form-group custom">', $html);
    }

    public function testRenderWithCustomContainerAttributes(): void
    {
        $html = $this->field('captcha')
            ->withSiteKey('test-key')
            ->containerAttributes(['class' => 'my-wrapper', 'data-theme' => 'dark'])
            ->render();

        $this->assertStringStartsWith('<div class="my-wrapper" data-theme="dark">', $html);
    }

    public function testRenderWithCustomContainerTag(): void
    {
        $html = $this->field('captcha')
            ->withSiteKey('test-key')
            ->containerTag('section')
            ->render();

        $this->assertStringStartsWith('<section class="mb-3">', $html);
        $this->assertStringEndsWith('</section>', $html);
    }

    public function testRenderWithFormModelShowsErrors(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class() extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult($result);

        $html = $this->field('captcha', $formModel)->withSiteKey('test-key')->render();

        $this->assertStringContainsString('The captcha is invalid.', $html);
    }

    public function testRenderWithFormModelShowsNoErrorsForDifferentAttribute(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class() extends FormModel {
            public string $captcha = '';
            public string $other = '';
        };
        $formModel->processValidationResult($result);

        $html = $this->field('other', $formModel)->withSiteKey('test-key')->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function testRenderWithFormModelShowsNoErrorsWhenNoErrors(): void
    {
        $formModel = new class() extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult(new Result());

        $html = $this->field('captcha', $formModel)->withSiteKey('test-key')->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function testRenderWithoutContainer(): void
    {
        $html = $this->field('captcha')->withSiteKey('test-key')->useContainer(false)->render();

        $this->assertStringNotContainsString('mb-3', $html);
        $this->assertStringStartsNotWith('<div class="mb-3">', $html);
    }

    public function testRenderWrappedInDivWithMb3ByDefault(): void
    {
        $html = $this->field('captcha')->withSiteKey('test-key')->render();

        $this->assertStringStartsWith('<div class="mb-3">' . "\n", $html);
        $this->assertStringEndsWith("\n" . '</div>', $html);
    }

    public function testThrowsWhenRegistryHasNoClientConfigured(): void
    {
        $reflection = new ReflectionClass(RecaptchaRegistry::class);
        $property = $reflection->getProperty('client');
        $property->setValue(null, null);

        $this->expectException(MissingSiteKeyException::class);

        $this->field('captcha')->render();
    }

    public function testThrowsWithoutSiteKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Site key must be set');

        $this->field('captcha')->render();
    }

    protected function createFormModel(): FormModel
    {
        return new class() extends FormModel {
            public string $captcha = '';
        };
    }

    protected function field(string $attribute = 'captcha', ?FormModel $formModel = null): object
    {
        $class = $this->fieldClass();

        return $class::field($formModel ?? $this->createFormModel(), $attribute);
    }

    /**
     * @return class-string
     */
    abstract protected function fieldClass(): string;
}
