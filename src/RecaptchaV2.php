<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Widget\Widget;

final class RecaptchaV2 extends Widget
{
    private const DefaultJsApiUrl = 'https://www.google.com/recaptcha/api.js';

    private ?string $siteKey = null;
    private string $id = '';
    private RecaptchaV2Theme $theme = RecaptchaV2Theme::Light;
    private RecaptchaV2Type $type = RecaptchaV2Type::Image;
    private RecaptchaV2Size $size = RecaptchaV2Size::Normal;
    private string $jsApiUrl = self::DefaultJsApiUrl;
    private string $callback = '';
    private string $expiredCallback = '';
    private string $errorCallback = '';

    public function withSiteKey(string $siteKey): self
    {
        $new = clone $this;
        $new->siteKey = $siteKey;
        return $new;
    }

    public function withId(string $id): self
    {
        $new = clone $this;
        $new->id = $id;
        return $new;
    }

    public function withTheme(RecaptchaV2Theme $theme): self
    {
        $new = clone $this;
        $new->theme = $theme;
        return $new;
    }

    public function withType(RecaptchaV2Type $type): self
    {
        $new = clone $this;
        $new->type = $type;
        return $new;
    }

    public function withSize(RecaptchaV2Size $size): self
    {
        $new = clone $this;
        $new->size = $size;
        return $new;
    }

    public function withJsApiUrl(string $url): self
    {
        $new = clone $this;
        $new->jsApiUrl = $url;
        return $new;
    }

    public function withCallback(string $callback): self
    {
        $new = clone $this;
        $new->callback = $callback;
        return $new;
    }

    public function withExpiredCallback(string $callback): self
    {
        $new = clone $this;
        $new->expiredCallback = $callback;
        return $new;
    }

    public function withErrorCallback(string $callback): self
    {
        $new = clone $this;
        $new->errorCallback = $callback;
        return $new;
    }

    public function render(): string
    {
        if ($this->siteKey === null || $this->siteKey === '') {
            throw new \RuntimeException('Site key must be set before rendering.');
        }

        $id = $this->id !== '' ? $this->id : 'g-recaptcha-' . uniqid();

        $html = '<div'
            . ' id="' . $id . '"'
            . ' class="g-recaptcha"'
            . ' data-sitekey="' . $this->siteKey . '"'
            . ' data-theme="' . $this->theme->value . '"'
            . ' data-type="' . $this->type->value . '"'
            . ' data-size="' . $this->size->value . '"';

        if ($this->callback !== '') {
            $html .= ' data-callback="' . $this->callback . '"';
        }
        if ($this->expiredCallback !== '') {
            $html .= ' data-expired-callback="' . $this->expiredCallback . '"';
        }
        if ($this->errorCallback !== '') {
            $html .= ' data-error-callback="' . $this->errorCallback . '"';
        }

        $html .= '></div>';

        $html .= "\n<script src=\"" . $this->jsApiUrl . "\" async defer></script>\n";

        return $html;
    }
}
