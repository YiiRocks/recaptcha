<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Form\Field\Base\InputField;
use Yiisoft\FormModel\FormModelInputData;
use Yiisoft\FormModel\FormModelInterface;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

final class RecaptchaV3Field extends InputField
{
    private const DEFAULT_JS_API_URL = 'https://www.google.com/recaptcha/api.js';

    private const DEFAULT_LEGAL_NOTICE = 'This site is protected by reCAPTCHA and the Google '
        . '<a href="' . self::PRIVACY_URL . '">' . self::PRIVACY_POLICY_TEXT . '</a> and '
        . '<a href="' . self::TERMS_URL . '">' . self::TERMS_OF_SERVICE_TEXT . '</a> apply.';
    private const HIDDEN_BADGE_STYLE = '<style>.grecaptcha-badge{visibility:hidden !important;}</style>';

    private const LEGAL_NOTICE_MESSAGE_ID = 'This site is protected by reCAPTCHA and the Google {privacyPolicy} and {termsOfService} apply.';

    private const PRIVACY_POLICY_TEXT = 'Privacy Policy';

    private const PRIVACY_URL = 'https://policies.google.com/privacy';
    private const TERMS_OF_SERVICE_TEXT = 'Terms of Service';
    private const TERMS_URL = 'https://policies.google.com/terms';
    private string $action = '';
    private RecaptchaV3Badge $badge = RecaptchaV3Badge::BottomRight;
    private int $executeTimeoutMs = 15000;
    private string $formId = '';
    private string $jsApiUrl = self::DEFAULT_JS_API_URL;

    private ?string $siteKey = null;
    private ?TranslatorInterface $translator = null;

    public static function field(FormModelInterface $formModel, string $attribute): static
    {
        return (new static())->inputData(new FormModelInputData($formModel, $attribute));
    }

    public function withAction(string $action): static
    {
        $new = clone $this;
        $new->action = $action;
        return $new;
    }

    public function withBadge(RecaptchaV3Badge $badge): static
    {
        $new = clone $this;
        $new->badge = $badge;
        return $new;
    }

    public function withExecuteTimeout(?int $ms): static
    {
        $new = clone $this;
        $new->executeTimeoutMs = $ms ?? 0;
        return $new;
    }

    public function withFormId(string $id): static
    {
        $new = clone $this;
        $new->formId = $id;
        return $new;
    }

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

    public function withTranslator(?TranslatorInterface $translator): static
    {
        $new = clone $this;
        $new->translator = $translator;
        return $new;
    }

    #[\Override]
    protected function beforeRender(): void
    {
        $siteKey = $this->siteKey ?? RecaptchaRegistry::client()?->getConfig()->siteKeyV3;
        if ($siteKey === null || $siteKey === '') {
            throw new Exception\MissingSiteKeyException();
        }
        $this->siteKey = $siteKey;

        $this->translator ??= RecaptchaRegistry::translator();

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
                if (!isset($this->containerAttributes[$key])) {
                    $this->containerAttributes[$key] = $value;
                }
            }
        }

        if (!isset($this->containerAttributes['class'])) {
            Html::addCssClass($this->containerAttributes, ['mb-3']);
        }
    }

    #[\Override]
    protected function generateInput(): string
    {
        /** @var string $name */
        $name = $this->getName();
        $fieldId = $this->inputId ?? ($name !== '' ? $name : 'g-recaptcha') . '-' . uniqid();
        $siteKey = $this->siteKey ?? throw new Exception\MissingSiteKeyException();
        $apiUrl = $this->jsApiUrl . '?render=' . $siteKey;

        $html = '';

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $html = self::HIDDEN_BADGE_STYLE;
            $html .= Html::div()->addStyle(['display' => 'none'])->open();
        }

        $html .= Html::script()->src($apiUrl)->render();
        /** @var non-empty-string $fieldId */
        $html .= Html::hiddenInput($name)->id($fieldId)->render();

        $jsOptions = json_encode(
            [
                'siteKey' => $this->siteKey,
                'action' => $this->action,
                'fieldId' => $fieldId,
                'formId' => $this->formId,
                'timeout' => $this->executeTimeoutMs,
            ],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
        );

        $html .= '<script>';
        $html .= '(function(){';
        $html .= 'var o=' . $jsOptions . ';';
        $html .= 'var f=document.getElementById(o.fieldId);';
        $html .= 'var form=o.formId?document.getElementById(o.formId):f.closest("form");';
        $html .= 'if(form){';
        $html .= 'form.addEventListener("submit",function(e){';
        $html .= 'e.preventDefault();';
        $html .= 'var formRef=this;';
        $html .= 'var fSubmitted=false;';
        $html .= 'function submitForm(){if(!fSubmitted){fSubmitted=true;HTMLFormElement.prototype.submit.call(formRef);}}';
        if ($this->executeTimeoutMs > 0) {
            $html .= 'if(o.timeout){setTimeout(submitForm,o.timeout);}';
        }
        $html .= 'try{';
        $html .= 'if(typeof grecaptcha!=="undefined"&&typeof grecaptcha.execute==="function"){';
        if ($this->action !== '') {
            $html .= 'grecaptcha.execute(o.siteKey,{action:o.action}).then(function(t){';
        } else {
            $html .= 'grecaptcha.execute(o.siteKey).then(function(t){';
        }
        $html .= 'f.value=t;submitForm();';
        $html .= '})["catch"](function(){submitForm();});';
        $html .= '}else{submitForm();}';
        $html .= '}catch(e){submitForm();}';
        $html .= '});';
        $html .= '}';
        $html .= '})();';
        $html .= '</script>';

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $html .= Html::div()->close();
        }

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $privacyLink = Html::a($this->translate(self::PRIVACY_POLICY_TEXT), self::PRIVACY_URL);
            $termsLink = Html::a($this->translate(self::TERMS_OF_SERVICE_TEXT), self::TERMS_URL);

            $notice = $this->translator !== null
                ? $this->translator->translate(
                    self::LEGAL_NOTICE_MESSAGE_ID,
                    ['privacyPolicy' => $privacyLink, 'termsOfService' => $termsLink],
                    'recaptcha',
                )
                : self::DEFAULT_LEGAL_NOTICE;

            $html .= Html::div($notice)->encode(false)->render();
        }

        return $html;
    }

    private function translate(string $message): string
    {
        if ($this->translator !== null) {
            return $this->translator->translate($message, [], 'recaptcha');
        }

        return $message;
    }
}
