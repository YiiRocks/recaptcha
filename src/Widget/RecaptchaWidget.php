<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Widget;

use YiiRocks\Recaptcha\RecaptchaConfig;

/**
 * Renders the reCAPTCHA widget HTML and script tag.
 *
 * RecaptchaWidget is a DI service — inject it directly into your action or view model:
 *
 * ```php
 * class ContactAction
 * {
 *     public function __construct(private readonly RecaptchaWidget $recaptcha) {}
 *
 *     public function __invoke(): ResponseInterface
 *     {
 *         // pass $this->recaptcha to the view
 *     }
 * }
 * ```
 *
 * Then in the view:
 *
 * ```php
 * echo $recaptcha->render();
 *
 * // With options:
 * echo $recaptcha->withTheme('dark')->withSize('compact')->render();
 * ```
 */
final class RecaptchaWidget
{
    private string  $theme           = 'light';
    private string  $size            = 'normal';
    private ?string $callback        = null;
    private ?string $expiredCallback = null;
    private ?string $errorCallback   = null;
    private string  $id              = 'recaptcha-container';
    private string  $fieldName       = 'g-recaptcha-response';
    private bool    $includeScript   = true;

    public function __construct(private readonly RecaptchaConfig $config) {}

    public function withTheme(string $theme): self
    {
        $clone = clone $this;
        $clone->theme = $theme;
        return $clone;
    }

    public function withSize(string $size): self
    {
        $clone = clone $this;
        $clone->size = $size;
        return $clone;
    }

    public function withCallback(string $callback): self
    {
        $clone = clone $this;
        $clone->callback = $callback;
        return $clone;
    }

    public function withExpiredCallback(string $callback): self
    {
        $clone = clone $this;
        $clone->expiredCallback = $callback;
        return $clone;
    }

    public function withErrorCallback(string $callback): self
    {
        $clone = clone $this;
        $clone->errorCallback = $callback;
        return $clone;
    }

    public function withId(string $id): self
    {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function withFieldName(string $fieldName): self
    {
        $clone = clone $this;
        $clone->fieldName = $fieldName;
        return $clone;
    }

    /** Skip rendering the <script> tag if you load it separately. */
    public function withoutScript(): self
    {
        $clone = clone $this;
        $clone->includeScript = false;
        return $clone;
    }

    public function render(): string
    {
        return match ($this->config->getVersion()) {
            RecaptchaConfig::VERSION_V3           => $this->renderV3(),
            RecaptchaConfig::VERSION_V2_INVISIBLE => $this->renderV2Invisible(),
            default                               => $this->renderV2Checkbox(),
        };
    }

    private function renderV2Checkbox(): string
    {
        $attrs = $this->buildDataAttributes([
            'sitekey'          => $this->config->getSiteKey(),
            'theme'            => $this->theme,
            'size'             => $this->size,
            'callback'         => $this->callback,
            'expired-callback' => $this->expiredCallback,
            'error-callback'   => $this->errorCallback,
        ]);

        $script = $this->includeScript
            ? $this->scriptTag('https://www.google.com/recaptcha/api.js')
            : '';

        return <<<HTML
            {$script}
            <div id="{$this->esc($this->id)}" class="g-recaptcha"{$attrs}></div>
            HTML;
    }

    private function renderV2Invisible(): string
    {
        $callback = $this->callback ?? 'onRecaptchaSubmit';
        $attrs    = $this->buildDataAttributes([
            'sitekey'  => $this->config->getSiteKey(),
            'callback' => $callback,
            'size'     => 'invisible',
        ]);

        $script = $this->includeScript
            ? $this->scriptTag('https://www.google.com/recaptcha/api.js')
            : '';

        return <<<HTML
            {$script}
            <div id="{$this->esc($this->id)}" class="g-recaptcha"{$attrs}></div>
            HTML;
    }

    private function renderV3(): string
    {
        $siteKey = $this->esc($this->config->getSiteKey());
        $action  = $this->esc($this->config->getV3Action());
        $field   = $this->esc($this->fieldName);

        $apiScript = $this->includeScript
            ? $this->scriptTag("https://www.google.com/recaptcha/api.js?render={$siteKey}")
            : '';

        $inlineJs = <<<JS
            grecaptcha.ready(function () {
                grecaptcha.execute('{$siteKey}', {action: '{$action}'}).then(function (token) {
                    var fields = document.querySelectorAll('input[name="{$field}"]');
                    fields.forEach(function (el) { el.value = token; });
                });
            });
            JS;

        return <<<HTML
            {$apiScript}
            <input type="hidden" name="{$field}">
            <script>{$inlineJs}</script>
            HTML;
    }

    /**
     * @param array<string, string|null> $attrs
     */
    private function buildDataAttributes(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $name => $value) {
            if ($value !== null) {
                $html .= ' data-' . $name . '="' . $this->esc($value) . '"';
            }
        }
        return $html;
    }

    private function scriptTag(string $src): string
    {
        return '<script src="' . $this->esc($src) . '" async defer></script>';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
