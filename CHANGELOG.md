# Yii reCAPTCHA Change Log

## 1.0.4 under development

- Chg: Extract shared abstract base classes `AbstractRecaptchaField`, `AbstractRecaptchaRule` and
  `AbstractRecaptchaRuleHandler` to de-duplicate the v2/v3 verticals (internal only, no public API change)
- Chg: De-duplicate field and rule-handler tests via shared abstract test bases `AbstractRecaptchaField`
  and `AbstractRecaptchaRuleHandler`

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
