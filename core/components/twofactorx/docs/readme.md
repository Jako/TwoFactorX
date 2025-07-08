# TwoFactorX

Add a two-factor TOTP authentication to the MODX manager.

- Author: Thomas Jakobi <office@treehillstudio.com>
- License: GNU GPLv2
- Based on the ideas and some code of GoogleAuthenticatorX by Mina Gerges <mina@minagerges.com>

## Features

With this package for MODX Revolution the manager login can be secured with a
two-factor authentication. The "Login" extra can be secured with a pre-hook and
a QR Code for saving the authenticator secret can be generated with a snippet.

## Fork

This package is a fork and a complete rewrite of GoogleAuthenticatorX package.
The reasons can be found on the [contributors]
(https://jako.github.io/TwoFactorX/contributors/) page.

## Installation

MODX Package Management

## Usage

Install via package manager and enable the system setting twofactorx.enable_2fa.
After that, you have to verify the TOTP secret for the manager user. When it is
verified, the manager user has to use the TOTP key to login into the manager.

## Documentation

For more information please read the documentation on
https://jako.github.io/TwoFactorX/

## GitHub Repository

https://github.com/Jako/TwoFactorX

## Translations

Translations of TwoFactorX can be made on
[Weblate](https://hosted.weblate.org/projects/modx-extras/twofactorx/)

## Third party licenses

This extra includes third party software, for which we are thankful.

* bacon/bacon-qr-code@2.0.8 [BSD-2-Clause]
* dasprid/enum@1.0.6 [BSD-2-Clause]
* endroid/qr-code@4.6.1 [MIT]
* phpgangsta/googleauthenticator@dev-master 505c2af [BSD-4-Clause]