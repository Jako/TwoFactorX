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
<p>Sie erhalten diese E-Mail, weil die Zwei-Faktor-Authentifizierung für Ihr Konto aktiviert wurde. Um sie zu nutzen, benötigen Sie ein unterstütztes Gerät oder eine Anwendung, die ein zeitbasiertes Einmalpasswort (TOTP) generiert, z.B. Google Authenticator, Authy oder 1Password.</p>
<p>Sie können sich einmal ohne Authentifizierungsschlüssel im Manager anmelden. Anschließend zeigt ein Fenster bei jedem Laden des Managers Ihren QR-Code und Ihr Secret an. Scannen Sie den QR-Code mit der Authentifizierungsanwendung und geben Sie den angezeigten Authentifizierungsschlüssel zum Verifizieren in dem Fenster ein. Danach zeigt die Authentifizierungsanwendung alle 30 Sekunden einen neuen Authentifizierungsschlüssel an, den Sie bei jeder Anmeldung benötigen.</p>
<hr/>
<div style="margin-top: 0px;">
<h4>Einrichten der Anwendung</h4>
<ol>
<li>Öffnen Sie die Authentifizierungsanwendung.</li>
<li>Um Ihr unterstütztes Gerät oder Ihre Anwendung mit Ihrem Konto zu verknüpfen, scannen Sie den QR-Code oder fügen Sie den Namen und das Secret in der Anwendung hinzu.</li>
</ol>
</div>';
$_lang['twofactorx.notifyemail_subject'] = 'Die Zwei-Faktor-Authentifizierung bei der Anmeldung wurde aktiviert';
$_lang['twofactorx.qremail_body'] = '<p>Hallo [[+username]],</p>
<p>Um die Zwei-Faktor-Authentifizierung zu nutzen, benötigen Sie ein unterstütztes Gerät oder eine Anwendung, die ein zeitbasiertes Einmalpasswort (TOTP) generiert, z.B. Google Authenticator, Authy oder 1Password. Öffnen Sie die Authentifizierungsanwendung und scannen Sie den bereitgestellten QR-Code. Sobald dieser Vorgang erfolgreich abgeschlossen ist, wird in der Authentifizierungsanwendung alle 30 Sekunden ein neuer Authentifizierungsschlüssel angezeigt. Dieser Schlüssel wird nun bei der Anmeldung benötigt.</p>
<p><img src="cid:qr-code" alt="QR-Code"/></p>
<p>Secret: [[+secret]]</p>';
$_lang['twofactorx.qremail_subject'] = 'Ihr QR-Code für die Zwei-Faktor-Authentifizierung';
