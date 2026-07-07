<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\FormModel\FormModelInputData;
use Yiisoft\FormModel\FormModelInterface;

final class RecaptchaV2Field extends AbstractRecaptchaField
{

    private string $callback = '';
    private string $errorCallback = '';
    private string $expiredCallback = '';
    private string $id = '';
    private RecaptchaV2Size $size = RecaptchaV2Size::Normal;
    private RecaptchaV2Theme $theme = RecaptchaV2Theme::Light;
    private RecaptchaV2Type $type = RecaptchaV2Type::Image;
    public static function field(FormModelInterface $formModel, string $attribute): static
    {
        return (new static())->inputData(new FormModelInputData($formModel, $attribute));
    }

    public function withCallback(string $callback): static
    {
        $new = clone $this;
        $new->callback = $callback;
        return $new;
    }

    public function withErrorCallback(string $callback): static
    {
        $new = clone $this;
        $new->errorCallback = $callback;
        return $new;
    }

    public function withExpiredCallback(string $callback): static
    {
        $new = clone $this;
        $new->expiredCallback = $callback;
        return $new;
    }

    public function withId(string $id): static
    {
        $new = clone $this;
        $new->id = $id;
        return $new;
    }

    public function withSize(RecaptchaV2Size $size): static
    {
        $new = clone $this;
        $new->size = $size;
        return $new;
    }

    public function withTheme(RecaptchaV2Theme $theme): static
    {
        $new = clone $this;
        $new->theme = $theme;
        return $new;
    }

    public function withType(RecaptchaV2Type $type): static
    {
        $new = clone $this;
        $new->type = $type;
        return $new;
    }

    #[\Override]
    protected function generateInput(): string
    {
        $elementId = $this->id !== '' ? $this->id : 'g-recaptcha-' . uniqid();

        $siteKey = $this->siteKey ?? throw new Exception\MissingSiteKeyException();

        $html = '<div'
            . ' id="' . $elementId . '"'
            . ' class="g-recaptcha"'
            . ' data-sitekey="' . $siteKey . '"'
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

    #[\Override]
    protected function prepareRender(): void
    {
    }

    #[\Override]
    protected function resolveSiteKey(): ?string
    {
        return RecaptchaRegistry::client()?->getConfig()->siteKeyV2;
    }
}
