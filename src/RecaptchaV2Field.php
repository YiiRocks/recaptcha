<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Form\Field\Base\InputField;
use Yiisoft\FormModel\FormModelInterface;
use Yiisoft\FormModel\FormModelInputData;
use Yiisoft\Html\Html;

final class RecaptchaV2Field extends InputField
{
    private const DEFAULT_JS_API_URL = 'https://www.google.com/recaptcha/api.js';

    private ?string $siteKey = null;
    private string $id = '';
    private RecaptchaV2Theme $theme = RecaptchaV2Theme::Light;
    private RecaptchaV2Type $type = RecaptchaV2Type::Image;
    private RecaptchaV2Size $size = RecaptchaV2Size::Normal;
    private string $jsApiUrl = self::DEFAULT_JS_API_URL;
    private string $callback = '';
    private string $expiredCallback = '';
    private string $errorCallback = '';

    public static function field(FormModelInterface $formModel, string $attribute): static
    {
        return (new static())->inputData(new FormModelInputData($formModel, $attribute));
    }

    public function withSiteKey(string $siteKey): static
    {
        $new = clone $this;
        $new->siteKey = $siteKey;
        return $new;
    }

    public function withId(string $id): static
    {
        $new = clone $this;
        $new->id = $id;
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

    public function withSize(RecaptchaV2Size $size): static
    {
        $new = clone $this;
        $new->size = $size;
        return $new;
    }

    public function withJsApiUrl(string $url): static
    {
        $new = clone $this;
        $new->jsApiUrl = $url;
        return $new;
    }

    public function withCallback(string $callback): static
    {
        $new = clone $this;
        $new->callback = $callback;
        return $new;
    }

    public function withExpiredCallback(string $callback): static
    {
        $new = clone $this;
        $new->expiredCallback = $callback;
        return $new;
    }

    public function withErrorCallback(string $callback): static
    {
        $new = clone $this;
        $new->errorCallback = $callback;
        return $new;
    }

    protected function beforeRender(): void
    {
        $siteKey = $this->siteKey ?? RecaptchaRegistry::client()?->getConfig()?->siteKeyV2;
        if ($siteKey === null || $siteKey === '') {
            throw new Exception\MissingSiteKeyException();
        }
        $this->siteKey = $siteKey;

        $this->template = "{input}\n{error}";
        $this->hideLabel = true;
        $this->inputContainerTag = null;
        $this->beforeInput = '';
        $this->afterInput = '';

        $useContainer = RecaptchaRegistry::containerUseContainer();
        if ($useContainer !== null && $this->useContainer === true) {
            $this->useContainer = $useContainer;
        }

        $tag = RecaptchaRegistry::containerTag();
        if ($tag !== null && $tag !== '' && $this->containerTag === 'div') {
            $this->containerTag = $tag;
        }

        $attributes = RecaptchaRegistry::containerAttributes();
        if ($attributes !== null) {
            foreach ($attributes as $key => $value) {
                if (!isset($this->containerAttributes[$key])) {
                    $this->containerAttributes[$key] = $value;
                }
            }
        }

        if (!isset($this->containerAttributes['class'])) {
            Html::addCssClass($this->containerAttributes, ['mb-3']);
        }
    }

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
}
