# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.9] - TBA

### Fixed

- Get the right email language in MODX 3
- Fix some small issues

## [1.0.8] - 2025-03-02

### Changed

- Mail the QR code image as PNG to avoid Webmailer issues (i.e. Gmail strips SVGs for security reasons)

## [1.0.7] - 2025-02-16

### Fixed

- Fix the QR code image in the email to the user [#8] – thanks to Hylke (https://github.com/hylkest)

## [1.0.6] - 2024-09-05

### Changed

- Update internal composer.phar to 2.7.9

## [1.0.5] - 2024-04-24

### Added

- Check the code in the TwoFactorXLogin hook only, when the user has to use the two-factor authentication.
- Show the QR code only with the UserQRcode snippet, when the user can use the two-factor authentication.

### Fixed

- Fix the return value of the TwoFactorXLogin hook.

## [1.0.4] - 2024-02-14

### Fixed

- Fix undefined array key warning

## [1.0.3] - 2024-02-10

### Changed

- Password managers can automatically fill the otp-field

## [1.0.2] - 2024-01-11

### Fixed

- Fix a wrong encoded URL in the QR code

## [1.0.1] - 2023-12-28

### Added

- Migrate GoogleAuthenticatorX settings and extended user fields

### Changed

- Some UI improvements
- German lexicon improvements

## [1.0.0] - 2023-12-25

### Added

- Initial Release as a complete rewrite of GoogleAuthenticatorX

### Changed

- Use internal QR code generation
- Name change because it is not limited to one two-factor authentication application
- MODX 3 compatibility
- Code simplification
- Common code structure for better use of the MODX API
- Rename some system settings
- Use MODX permissions in the user edit page and in the processors
- Use Composer for the third party software
