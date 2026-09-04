<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Form\Field\Base\InputField;
use Yiisoft\Html\Html;
use Override;

abstract class AbstractRecaptchaField extends InputField
{
    protected const string DEFAULT_JS_API_URL = 'https://www.google.com/recaptcha/api.js';

    protected string $jsApiUrl = self::DEFAULT_JS_API_URL;
    protected ?string $siteKey = null;

    public function withJsApiUrl(string $url): static
    {
        $new = clone $this;
        $new->jsApiUrl = $url;
        return $new;
    }

    public function withSiteKey(string $siteKey): static
    {
        $new = clone $this;
        $new->siteKey = $siteKey;
        return $new;
    }

    #[Override]
    protected function beforeRender(): void
    {
        $this->siteKey ??= $this->resolveSiteKey();

        if ($this->siteKey === null || $this->siteKey === '') {
            $this->useContainer = false;
            $this->template = '';
            return;
        }

        $this->prepareRender();

        $this->template = "{input}\n{error}";
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
                $this->containerAttributes[$key] ??= $value;
            }
        }

        if (!isset($this->containerAttributes['class'])) {
            Html::addCssClass($this->containerAttributes, ['mb-3']);
        }
    }

    abstract protected function prepareRender(): void;

    abstract protected function resolveSiteKey(): ?string;
}
