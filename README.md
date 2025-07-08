[![Default Lexicon](https://hosted.weblate.org/widget/modx-extras/twofactorx/standard/svg-badge.svg)](https://hosted.weblate.org/projects/modx-extras/twofactorx/)

# TwoFactorX

Add a two-factor TOTP authentication to the MODX manager. Secure the front end
login with a pre-hook for the "Login" extra. Generate a QR Code for saving the
authenticator secret with a snippet in the frontend.

## Installation

MODX Package Management

## Usage

Install via package manager. After you have saved the two-factor secret shown in
the TwoFactorX tab when you edit your manager user, you can enable the system
setting twofactorx.enable_2fa. When the system setting enable_2fa is enabled, a
manager user has to verify the TOTP secret. Otherwise, the authenticator secret
verify window is displayed with each manager load. When it is verified, the
manager user has to use the TOTP key to login into the manager. The TOTP
authentication can be disabled on manager user base.

## License

The project is licensed under the [GPLv2 license](https://github.com/Jako/TwoFactorX/blob/master/LICENSE.md).

## Translations

Translations of the package can be made for the [Default Lexicon](https://hosted.weblate.org/projects/modx-extras/twofactorx/standard/), the [Email Lexicon](https://hosted.weblate.org/projects/modx-extras/twofactorx/email/), the [Properties Lexicon](https://hosted.weblate.org/projects/modx-extras/twofactorx/properties/), the [Permissions Lexicon](https://hosted.weblate.org/projects/modx-extras/twofactorx/permissions/) and the [System Setting Lexicon](https://hosted.weblate.org/projects/modx-extras/twofactorx/system-settings/)
