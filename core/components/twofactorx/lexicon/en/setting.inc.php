<?php
/**
 * Setting Lexicon Entries for TwoFactorX
 *
 * @package twofactorx
 * @subpackage lexicon
 */
$_lang['setting_twofactorx.enable_onetime'] = 'Enable One-Time Login';
$_lang['setting_twofactorx.enable_onetime_desc'] = 'If you enable one-time login, users are allowed to log in once to retrieve their secret.';
$_lang['setting_twofactorx.debug'] = 'Debug';
$_lang['setting_twofactorx.debug_desc'] = 'Log debug information in the MODX error log.';
$_lang['setting_twofactorx.enable_2fa'] = 'Enable Two-Factor Authentication';
$_lang['setting_twofactorx.enable_2fa_desc'] = 'If you enable the two-factor authentication, the manager login is secured with an additional TOTP authentication code.';
$_lang['setting_twofactorx.encryption_key'] = 'Encryption Key';
$_lang['setting_twofactorx.encryption_key_desc'] = 'Encryption key that is used for the encryption of the 2FA data. Do not change.';
$_lang['setting_twofactorx.issuer'] = 'QR Code Issuer';
$_lang['setting_twofactorx.issuer_desc'] = 'Specify the value of the issuer in the QR code. The default value is the system setting site_name.';
$_lang['setting_twofactorx.show_in_profile'] = 'Show Secret In User Profile';
$_lang['setting_twofactorx.show_in_profile_desc'] = 'Allow manager users to see the QR code and the secret for two-factor authentication in their user profile.';
