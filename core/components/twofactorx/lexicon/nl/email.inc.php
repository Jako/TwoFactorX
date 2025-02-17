<?php
/**
 * E-mail Lexicon Ingangen voor TwoFactorX
 *
 * @package twofactorx
 * @subpackage lexicon
 */
$_lang['twofactorx.email_fail'] = 'E-mail verzenden mislukt.';
$_lang['twofactorx.email_success'] = 'E-mail succesvol verzonden.';
$_lang['twofactorx.notifyemail_body'] = '<p>Hallo [[+username]],</p>
<p>U ontvangt deze e-mail omdat 2FA is geactiveerd voor uw account. Om 2FA te gebruiken, heeft u een ondersteund apparaat of een applicatie nodig die een tijdgebonden eenmalig wachtwoord (TOTP) genereert, zoals Google Authenticator, Authy of 1Password. Zonder een authenticatiesleutel kunt u slechts één keer inloggen. Als u succesvol bent ingelogd, ontvangt u alleen uw QR-code en wordt u direct uitgelogd.</p>
<p>Open de authenticatie-applicatie en scan de verstrekte QR-code. Zodra dit proces succesvol is voltooid, wordt er elke 30 seconden een nieuwe authenticatiesleutel weergegeven in de authenticatie-applicatie.</p>
<p><strong>Let op. De QR-code is slechts 60 seconden zichtbaar op het scherm.</strong></p>
<hr/>
<div style="margin-top: 0px;">
<h4>De applicatie instellen</h4>
<ol>
<li>Open de authenticatie-applicatie.</li>
<li>Om uw ondersteunde apparaat of applicatie aan uw account te koppelen, moet u de QR-code scannen of de naam en geheime sleutel in de applicatie invoeren.</li>
</ol>
</div>';
$_lang['twofactorx.notifyemail_subject'] = '2FA is geactiveerd bij het inloggen';
$_lang['twofactorx.qremail_body'] = '<p>Hallo [[+username]],</p>
<p>Om de 2FA te gebruiken, heeft u een ondersteund apparaat of een applicatie nodig die een tijdgebonden eenmalig wachtwoord kan genereren, zoals Google Authenticator, Authy of 1Password. Open de authenticatie-applicatie en scan de verstrekte QR-code. Zodra dit proces succesvol is voltooid, wordt er elke 30 seconden een nieuwe authenticatiesleutel weergegeven in de authenticatie-applicatie. Deze sleutel is nu vereist bij het inloggen.</p>
<p>Geheim: [[+secret]]</p>';
$_lang['twofactorx.qremail_subject'] = 'Uw 2FA QR-code';