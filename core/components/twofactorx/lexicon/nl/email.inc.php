<?php
/**
 * Email Lexicon Entries for TwoFactorX
 *
 * @package twofactorx
 * @subpackage lexicon
 */
$_lang['twofactorx.email_fail'] = 'E-mail verzenden mislukt.';
$_lang['twofactorx.email_success'] = 'E-mail succesvol verzonden.';
$_lang['twofactorx.notifyemail_body'] = '<p>Hallo [[+username]],</p>
<p>U ontvangt deze e-mail omdat tweefactorauthenticatie is geactiveerd voor uw account. Om deze te gebruiken, heeft u een ondersteund apparaat of een applicatie nodig die een tijdgebonden eenmalig wachtwoord (TOTP) genereert, zoals Google Authenticator, Authy of 1Password.</p>
<p>U kunt eenmaal zonder authenticatiesleutel inloggen bij de Manager. Een venster toont dan telkens wanneer u de Manager laadt uw QR-code en uw secret. Scan de QR-code met de authenticatie-applicatie en voer de weergegeven authenticatiesleutel ter verificatie in het venster in. Vervolgens toont de authenticatie-applicatie elke 30 seconden een nieuwe authenticatiesleutel, die u bij elke aanmelding nodig heeft.</p>
<hr/>
<div style="margin-top: 0px;">
<h4>De applicatie instellen</h4>
<ol>
<li>Open de authenticatie-applicatie.</li>
<li>Om uw ondersteunde apparaat of applicatie aan uw account te koppelen, scant u de QR-code of voert u de naam en het secret in de applicatie in.</li>
</ol>
</div>';
$_lang['twofactorx.notifyemail_subject'] = 'Tweefactorauthenticatie is geactiveerd voor uw aanmelding';
$_lang['twofactorx.qremail_body'] = '<p>Hallo [[+username]],</p>
<p>Om de 2FA te gebruiken, heeft u een ondersteund apparaat of een applicatie nodig die een tijdgebonden eenmalig wachtwoord kan genereren, zoals Google Authenticator, Authy of 1Password. Open de authenticatie-applicatie en scan de verstrekte QR-code. Zodra dit proces succesvol is voltooid, wordt er elke 30 seconden een nieuwe authenticatiesleutel weergegeven in de authenticatie-applicatie. Deze sleutel is nu vereist bij het inloggen.</p>
<p><img src="cid:qr-code" alt="QR Code"/></p>
<p>Secret: [[+secret]]</p>';
$_lang['twofactorx.qremail_subject'] = 'Uw 2FA QR-code';
