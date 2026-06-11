<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use PHPUnit\Framework\TestCase;
use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Type;
use YiiRocks\Recaptcha\RecaptchaV3Badge;

final class RecaptchaEnumTest extends TestCase
{
    public function testV2SizeValues(): void
    {
        $this->assertSame('normal', RecaptchaV2Size::Normal->value);
        $this->assertSame('compact', RecaptchaV2Size::Compact->value);
        $this->assertSame('invisible', RecaptchaV2Size::Invisible->value);
    }

    public function testV2ThemeValues(): void
    {
        $this->assertSame('light', RecaptchaV2Theme::Light->value);
        $this->assertSame('dark', RecaptchaV2Theme::Dark->value);
    }

    public function testV2TypeValues(): void
    {
        $this->assertSame('image', RecaptchaV2Type::Image->value);
        $this->assertSame('audio', RecaptchaV2Type::Audio->value);
    }

    public function testV3BadgeValues(): void
    {
        $this->assertSame('bottomright', RecaptchaV3Badge::BottomRight->value);
        $this->assertSame('bottomleft', RecaptchaV3Badge::BottomLeft->value);
        $this->assertSame('hidden', RecaptchaV3Badge::Hidden->value);
    }
}
