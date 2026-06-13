<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Form\Field\Base\InputField;
use Yiisoft\FormModel\FormModelInterface;
use Yiisoft\FormModel\FormModelInputData;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;

final class RecaptchaV3Field extends InputField
{
    private const DEFAULT_JS_API_URL = 'https://www.google.com/recaptcha/api.js';

    private const PRIVACY_URL = 'https://policies.google.com/privacy';
    private const TERMS_URL = 'https://policies.google.com/terms';

    private const ANCHOR_OPEN = '<a href="';
    private const ANCHOR_CLOSE = '</a>';

    private const PRIVACY_POLICY_TEXT = 'Privacy Policy';
    private const TERMS_OF_SERVICE_TEXT = 'Terms of Service';

    private const DEFAULT_LEGAL_NOTICE = 'This site is protected by reCAPTCHA and the Google '
        . self::ANCHOR_OPEN . self::PRIVACY_URL . '">' . self::PRIVACY_POLICY_TEXT . self::ANCHOR_CLOSE . ' and '
        . self::ANCHOR_OPEN . self::TERMS_URL . '">' . self::TERMS_OF_SERVICE_TEXT . self::ANCHOR_CLOSE . ' apply.';

    private const LEGAL_NOTICE_MESSAGE_ID = 'This site is protected by reCAPTCHA and the Google {privacyPolicy} and {termsOfService} apply.';

    private ?string $siteKey = null;
    private string $action = '';
    private string $formId = '';
    private RecaptchaV3Badge $badge = RecaptchaV3Badge::BottomRight;
    private string $jsApiUrl = self::DEFAULT_JS_API_URL;
    private ?TranslatorInterface $translator = null;
    private int $executeTimeoutMs = 15000;

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

    public function withAction(string $action): static
    {
        $new = clone $this;
        $new->action = $action;
        return $new;
    }

    public function withFormId(string $id): static
    {
        $new = clone $this;
        $new->formId = $id;
        return $new;
    }

    public function withBadge(RecaptchaV3Badge $badge): static
    {
        $new = clone $this;
        $new->badge = $badge;
        return $new;
    }

    public function withJsApiUrl(string $url): static
    {
        $new = clone $this;
        $new->jsApiUrl = $url;
        return $new;
    }

    public function withTranslator(?TranslatorInterface $translator): static
    {
        $new = clone $this;
        $new->translator = $translator;
        return $new;
    }

    public function withExecuteTimeout(?int $ms): static
    {
        $new = clone $this;
        $new->executeTimeoutMs = $ms ?? 0;
        return $new;
    }

    protected function beforeRender(): void
    {
        $siteKey = $this->siteKey ?? RecaptchaRegistry::client()?->getConfig()?->siteKeyV3;
        if ($siteKey === null || $siteKey === '') {
            throw new Exception\MissingSiteKeyException();
        }
        $this->siteKey = $siteKey;

        $this->translator ??= RecaptchaRegistry::translator();

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
        if ($tag !== null && $this->containerTag === 'div') {
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
        $name = $this->getName();
        $fieldId = $this->inputId ?? ($name !== '' ? $name : 'g-recaptcha') . '-' . uniqid();
        $apiUrl = $this->jsApiUrl . '?render=' . $this->siteKey;

        $html = '';

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $html .= '<div style="display:none;">';
        }

        $html .= '<script src="' . $apiUrl . '"></script>';
        $html .= "\n";

        $html .= '<input type="hidden" id="' . $fieldId . '" name="' . $name . '" value="">';
        $html .= "\n";

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
            $html .= '</div>';
        }

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $privacyLink = self::ANCHOR_OPEN . self::PRIVACY_URL . '">'
                . $this->translate(self::PRIVACY_POLICY_TEXT) . self::ANCHOR_CLOSE;
            $termsLink = self::ANCHOR_OPEN . self::TERMS_URL . '">'
                . $this->translate(self::TERMS_OF_SERVICE_TEXT) . self::ANCHOR_CLOSE;

            $notice = $this->translator !== null
                ? $this->translator->translate(
                    self::LEGAL_NOTICE_MESSAGE_ID,
                    ['privacyPolicy' => $privacyLink, 'termsOfService' => $termsLink],
                    'yii3-recaptcha',
                )
                : self::DEFAULT_LEGAL_NOTICE;

            $html .= "\n" . '<div>'
                . $notice
                . '</div>';
        }

        return $html;
    }

    private function translate(string $message): string
    {
        if ($this->translator !== null) {
            return $this->translator->translate($message, [], 'yii3-recaptcha');
        }

        return $message;
    }
}
