The TwoFactorX project was started in 2023 by [Thomas
Jakobi](https://github.com/jako) as a complete rewite of the GoogleAuthenticatorX
package by [Mina Gerges](https://github.com/minagerges).

The primary reason for the rewrite was the usage of an external service to
create the QR code. This is insecure and should not be used for sites, where
absolute security is needed. The current code uses an internal QR code
generation.

The name change was made because the authentication code can be used with other
two-factor authentication applications such as Authy, 1Password etc.

The other code changes are the follwing:

* MODX 3 compatibility
* code simplification
* common code structure for better use of the MODX API
* rename some system settings
* use MODX permissions in the user edit page and in the processors
* use Composer for the third party software

Many thanks to all who contributed, whether by creating pull requests,
submitting bug reports, or donating.
