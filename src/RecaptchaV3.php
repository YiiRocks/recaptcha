<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Widget\Widget;

final class RecaptchaV3 extends Widget
{
    private const DefaultJsApiUrl = 'https://www.google.com/recaptcha/api.js';

    private const PrivacyUrl = 'https://policies.google.com/privacy';
    private const TermsUrl = 'https://policies.google.com/terms';

    private const DefaultLegalNotice = 'This site is protected by reCAPTCHA and the Google '
        . '<a href="' . self::PrivacyUrl . '">Privacy Policy</a> and '
        . '<a href="' . self::TermsUrl . '">Terms of Service</a> apply.';

    private const LegalNoticeMessageId = 'This site is protected by reCAPTCHA and the Google {privacyPolicy} and {termsOfService} apply.';

    private ?string $siteKey = null;
    private string $action = 'submit';
    private string $fieldName = 'g-recaptcha-response';
    private string $fieldId = '';
    private string $formId = '';
    private RecaptchaV3Badge $badge = RecaptchaV3Badge::BottomRight;
    private string $jsApiUrl = self::DefaultJsApiUrl;
    private ?TranslatorInterface $translator = null;

    public function withSiteKey(string $siteKey): self
    {
        $new = clone $this;
        $new->siteKey = $siteKey;
        return $new;
    }

    public function withAction(string $action): self
    {
        $new = clone $this;
        $new->action = $action;
        return $new;
    }

    public function withFieldName(string $name): self
    {
        $new = clone $this;
        $new->fieldName = $name;
        return $new;
    }

    public function withFieldId(string $id): self
    {
        $new = clone $this;
        $new->fieldId = $id;
        return $new;
    }

    public function withFormId(string $id): self
    {
        $new = clone $this;
        $new->formId = $id;
        return $new;
    }

    public function withBadge(RecaptchaV3Badge $badge): self
    {
        $new = clone $this;
        $new->badge = $badge;
        return $new;
    }

    public function withJsApiUrl(string $url): self
    {
        $new = clone $this;
        $new->jsApiUrl = $url;
        return $new;
    }

    public function withTranslator(?TranslatorInterface $translator): self
    {
        $new = clone $this;
        $new->translator = $translator;
        return $new;
    }

    public function render(): string
    {
        $siteKey = $this->siteKey ?? RecaptchaRegistry::client()?->getConfig()?->siteKeyV3;

        if ($siteKey === null || $siteKey === '') {
            throw new \RuntimeException('Site key must be set before rendering.');
        }

        $this->siteKey = $siteKey;

        $this->translator ??= RecaptchaRegistry::translator();

        $fieldId = $this->fieldId !== '' ? $this->fieldId : $this->fieldName . '-' . uniqid();
        $apiUrl = $this->jsApiUrl . '?render=' . $this->siteKey;

        $html = '';

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $html .= '<div style="display:none;">';
        }

        $html .= '<script src="' . $apiUrl . '"></script>';
        $html .= "\n";

        $html .= '<input type="hidden" id="' . $fieldId . '" name="' . $this->fieldName . '" value="">';
        $html .= "\n";

        $jsOptions = json_encode(
            [
                'siteKey' => $this->siteKey,
                'action' => $this->action,
                'fieldId' => $fieldId,
                'formId' => $this->formId,
            ],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR,
        );

        $html .= '<script>';
        $html .= '(function(){';
        $html .= 'var o=' . $jsOptions . ';';
        $html .= 'var f=document.getElementById(o.fieldId);';
        $html .= 'grecaptcha.ready(function(){';

        if ($this->formId !== '') {
            $html .= 'document.getElementById(o.formId).addEventListener("submit",function(e){';
            $html .= 'e.preventDefault();';
            $html .= 'grecaptcha.execute(o.siteKey,{action:o.action}).then(function(t){';
            $html .= 'f.value=t;';
            $html .= 'HTMLFormElement.prototype.submit.call(this);';
            $html .= '});';
            $html .= '});';
        } else {
            $html .= 'grecaptcha.execute(o.siteKey,{action:o.action}).then(function(t){';
            $html .= 'f.value=t;';
            $html .= '});';
        }

        $html .= '});';
        $html .= '})();';
        $html .= '</script>';

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $html .= '</div>';
        }

        if ($this->badge === RecaptchaV3Badge::Hidden) {
            $privacyLink = '<a href="' . self::PrivacyUrl . '">'
                . $this->translate('Privacy Policy') . '</a>';
            $termsLink = '<a href="' . self::TermsUrl . '">'
                . $this->translate('Terms of Service') . '</a>';

            $notice = $this->translator !== null
                ? $this->translator->translate(
                    self::LegalNoticeMessageId,
                    ['privacyPolicy' => $privacyLink, 'termsOfService' => $termsLink],
                    'yii3-recaptcha',
                )
                : self::DefaultLegalNotice;

            $html .= "\n" . '<div>'
                . $notice
                . '</div>';
        }

        $html .= "\n";

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
