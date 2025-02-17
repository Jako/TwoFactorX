<?php
/**
 * Email Lexicon Entries for TwoFactorX
 *
 * @package twofactorx
 * @subpackage lexicon
 */
$_lang['twofactorx.email_fail'] = 'E-Mail konnte nicht gesendet werden.';
$_lang['twofactorx.email_success'] = 'E-Mail erfolgreich gesendet.';
$_lang['twofactorx.notifyemail_body'] = '<p>Hallo [[+username]],</p>
<p>Sie erhalten diese E-Mail, weil die Zwei-Faktor-Authentifizierung für Ihr Konto aktiviert wurde. Um die Zwei-Faktor-Authentifizierung zu nutzen, benötigen Sie ein unterstütztes Gerät oder eine Anwendung, die ein zeitbasiertes Einmalpasswort (TOTP) generiert, z.B. Google Authenticator, Authy oder 1Password. Ohne einen Authentifizierungsschlüssel können Sie sich nur einmal anmelden. Wenn Sie sich erfolgreich angemeldet haben, erhalten Sie nur Ihren QR-Code und werden sofort abgemeldet.</p>
<p>Öffnen Sie die Authentifizierungsanwendung und scannen Sie den mitgelieferten QR-Code. Sobald dieser Vorgang erfolgreich abgeschlossen ist, wird in der Authentifizierungsanwendung alle 30 Sekunden ein neuer Authentifizierungsschlüssel angezeigt.</p>
<p><strong>Bitte beachten Sie. Der QR-Code ist nur 60 Sekunden lang auf dem Bildschirm sichtbar.</strong></p>
<hr/>
<div style="margin-top: 0px;">
<h4>Einrichten der Anwendung</h4>
<ol>
<li>Öffnen Sie die Authentifizierungsanwendung.</li>
<li>Um Ihr unterstütztes Gerät oder Ihre Anwendung mit Ihrem Konto zu verknüpfen, müssen Sie den QR-Code scannen oder den Namen und das Secret in der Anwendung hinzufügen.</li>
</ol>
</div>';
$_lang['twofactorx.notifyemail_subject'] = 'Die Zwei-Faktor-Authentifizierung bei der Anmeldung wurde aktiviert';
$_lang['twofactorx.qremail_body'] = '<p>Hallo [[+username]],</p>
<p>Um die Zwei-Faktor-Authentifizierung zu nutzen, benötigen Sie ein unterstütztes Gerät oder eine Anwendung, die ein zeitbasiertes Einmal-Passwort (TOTP) zu generieren, z. B. Google Authenticator, Authy oder 1Password. Öffnen Sie die Authentifizierungsanwendung und scannen den bereitgestellten QR-Code. Sobald dieser Vorgang erfolgreich abgeschlossen ist, wird in der Authentifizierungsanwendung alle 30 Sekunden ein neuer Authentifizierungsschlüssel angezeigt. Dieser Schlüssel wird nun beim Einloggen benötigt.</p>
<p>Secret: [[+secret]]</p>';
$_lang['twofactorx.qremail_subject'] = 'Ihr QR-Code für die Zwei-Faktor-Authentifizierung';
