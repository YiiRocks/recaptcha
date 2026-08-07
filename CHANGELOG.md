# Yii reCAPTCHA Change Log

## 1.0.6 under development

## 1.0.5 August 07, 2026

- New: Add `badgeV3` param (`config/params.php`) for setting the v3 reCAPTCHA badge position/visibility
  app-wide via `RecaptchaRegistry`; per-field `RecaptchaV3Field::withBadge()` still overrides it
- New: Add `themeV2`, `sizeV2` and `typeV2` params for setting the v2 widget theme/size/type app-wide via
  `RecaptchaRegistry`; per-field `RecaptchaV2Field::withTheme()`/`withSize()`/`withType()` still override them

## 1.0.4 July 24, 2026

- Chg: Extract shared abstract base classes `AbstractRecaptchaField`, `AbstractRecaptchaRule` and
  `AbstractRecaptchaRuleHandler` to de-duplicate the v2/v3 verticals (internal only, no public API change)
- Chg: De-duplicate field and rule-handler tests via shared abstract test bases `AbstractRecaptchaField`
  and `AbstractRecaptchaRuleHandler`
- Chg: Change license from MIT to BSD-3-Clause; add homepage and funding links to `composer.json`
- Chg: Remove `translation.category` config option — `recaptcha` is now the hardcoded message category
- Chg: Trim `README.md` to a short overview pointing to the full docs at yii.rocks/recaptcha
- Bug: Lower `RecaptchaV3Field`'s default `withExecuteTimeout()` from 15000ms to 5000ms — the fallback
  submit timer was leaving the form looking hung for far too long if `grecaptcha.execute()` stalls

## 1.0.3 July 04, 2026

- New: Add Infection mutation testing via `composer infection` (min-msi=100, 100% MSI) (@Mister-42)
- Enh: Type class constants using PHP 8.3 typed class constants (@Mr. 42)
- Bug: Fix psalm level 1 errors and add `#[\Override]` attributes (@Mister-42)

## 1.0.2 June 25, 2026

- Chg: Rename translation category to `recaptcha` and fix psalm level 1 errors (@Mister-42)
- Bug: Fix psalm version constraint `@stable` -> `^6.0` so CI installs a modern Psalm (@Mister-42)

## 1.0.1 June 13, 2026

- Chg: Update composer description: widget -> field (@Mister-42)
- Chg: Refactor to `InputField` hierarchy and restore global container defaults (@Mister-42)
- Bug: Fix README: document field action default `''` and pairing guidance (@Mister-42)
- Bug: Fix default action mismatch: field sends no action by default, matching rule `null` default (@Mister-42)

## 1.0.0 June 13, 2026

- Initial release.
