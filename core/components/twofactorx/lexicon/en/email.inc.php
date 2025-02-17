<?php
/**
 * Email Lexicon Entries for TwoFactorX
 *
 * @package twofactorx
 * @subpackage lexicon
 */
$_lang['twofactorx.email_fail'] = 'Failed to send email.';
$_lang['twofactorx.email_success'] = 'Email sent successfully.';
$_lang['twofactorx.notifyemail_body'] = '<p>Hello [[+username]],</p>
<p>You are receiving this email because two-factor authentication has been activated for your account. To use two-factor authentication, you need a supported device or an application that generates a time-based one-time password (TOTP), e.g. Google Authenticator, Authy or 1Password. Without an authentication key, you can only log in once. If you have successfully logged in, you will only receive your QR code and will be logged out immediately.</p>
<p>Open the authentication application and scan the QR code provided. Once this process has been successfully completed, a new authentication key will be displayed in the authentication application every 30 seconds.</p>
<p><strong>Please note. The QR code is only visible on the screen for 60 seconds.</strong></p>
<hr/>
<div style="margin-top: 0px;">
<h4>Setting up the application</h4>
<ol>
<li>Open the authentication application.</li>
<li>To link your supported device or application to your account you have to scan the QR code or add name and the secret key in the application.</li>
</ol>
</div>';
$_lang['twofactorx.notifyemail_subject'] = 'Two-factor authentication has been activated in the login';
$_lang['twofactorx.qremail_body'] = '<p>Hello [[+username]],</p>
<p>To use the two-factor authentication, you need a supported device or application that can generate a time-based one-time password, e.g. Google Authenticator, Authy or 1Password. Open the authentication application and scan the QR code provided. Once this process has been successfully completed, a new authentication key will be displayed in the authentication application every 30 seconds. This key is now required when logging in.</p>
<p>Secret: [[+secret]]</p>';
$_lang['twofactorx.qremail_subject'] = 'Your Two-factor authentication QR-code';
