<?php
/**
 * Email Secret
 *
 * @package twofactorx
 * @subpackage processors
 */

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Writer\PngWriter;
use TreehillStudio\TwoFactorX\Processors\Processor;

//// Compatibility between 2.x/3.x
if (!class_exists('modMail')) {
    class_alias(\MODX\Revolution\Mail\modMail::class, \modMail::class);
}
if (!class_exists('modPHPMailer')) {
    class_alias(\MODX\Revolution\Mail\modPHPMailer::class, \modPHPMailer::class);
}

class TwoFactorXEmailSecretProcessor extends Processor
{
    public $permission = 'twofactorx_edit';

    public function process()
    {
        $userid = $this->getProperty('id');

        $this->twofactorx->loadUserByID($userid);
        $settings = $this->twofactorx->getDecryptedSettings();

        if ($settings) {
            $settings = array_merge($settings, [
                'accountname' => $this->twofactorx->userName,
                'issuer' => $this->twofactorx->getOption('issuer'),
            ]);

            $qrcode = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($this->twofactorx->getUri($settings['accountname'], $settings['secret'], $settings['issuer']))
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
                ->size(200)
                ->margin(0)
                ->roundBlockSizeMode(new RoundBlockSizeModeNone())
                ->build();
            $qrpng = $qrcode->getString();

            /** @var modUser $user */
            $user = $this->modx->getObject('modUser', $userid);
            $mgrLanguage = $user->getOption('manager_language');
            $mgrLanguage = $mgrLanguage ?: $this->modx->getOption('cultureKey');

            $this->modx->lexicon->load($mgrLanguage . ':twofactorx:email');
            $subject = $this->modx->lexicon('twofactorx.qremail_subject');

            $body = '<html><body>' . $this->modx->lexicon('twofactorx.qremail_body', [
                    'username' => $this->twofactorx->userName,
                    'secret' => $settings['secret'],
                ]) . '</body></html>';

            if (!$this->sendEmail($body, $user, [
                'subject' => $subject,
                'attachments' => [
                    [
                        'content' => $qrpng,
                        'cid' => 'qr-code',
                        'name' => 'qrcode.png',
                        'encoding' => 'base64',
                        'mime' => 'image/png'
                    ]
                ]
            ])) {
                $errorInfo = $this->modx->mail->mailer->ErrorInfo;
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Failed to send mail: ' . $errorInfo);
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.email_fail') . $errorInfo);
            }
            return $this->modx->error->success($this->modx->lexicon('twofactorx.email_success'));
        } else {
            return $this->modx->error->failure($this->modx->lexicon('twofactorx.invaliddata'));
        }
    }

    /**
     *  Sends an email with the QR code as an attachment
     *
     * @param string $message
     * @param modUser $user
     * @param array $options
     * @return bool
     */
    public function sendEmail($message, $user, $options = [])
    {
        /** @var modUserProfile $profile */
        $profile = $user->getOne('Profile');
        /** @var modPHPMailer $mail */
        if ($this->twofactorx->getOption('modxversion') >= 3) {
            if (!$this->modx->services->has('mail')) {
                $this->modx->services->add('mail', new modPHPMailer($this->modx));
            }
            $mail = $this->modx->services->get('mail');
        } else {
            $mail = $this->modx->getService('mail', 'mail.modPHPMailer');
        }

        if (!$profile || !$mail) {
            return false;
        }
        $mail->set(modMail::MAIL_BODY, $message);
        $mail->set(modMail::MAIL_FROM, $this->modx->getOption('from', $options, $this->modx->getOption('emailsender')));
        $mail->set(modMail::MAIL_FROM_NAME,
            $this->modx->getOption('fromName', $options, $this->modx->getOption('site_name')));
        $mail->set(modMail::MAIL_SENDER,
            $this->modx->getOption('sender', $options, $this->modx->getOption('emailsender')));
        $mail->set(modMail::MAIL_SUBJECT,
            $this->modx->getOption('subject', $options['subject'], $this->modx->lexicon('twofactorx.qremail_subject')));
        $mail->address('to', $profile->get('email'), $profile->get('fullname'));
        $mail->address('reply-to', $this->modx->getOption('sender', $options, $this->modx->getOption('emailsender')));

        foreach ($options['attachments'] as $attachment) {
            $mail->mailer->addStringEmbeddedImage($attachment['content'], $attachment['cid'], $attachment['name'], $attachment['encoding'], $attachment['mime']);
        }

        $mail->setHTML($this->modx->getOption('html', $options, true));
        if (!$sent = $mail->send()) {
            $err = $this->modx->lexicon('error_sending_email_to') . $profile->get('email') . ': ' . $mail->mailer->ErrorInfo;
            $this->modx->log(modx::LOG_LEVEL_ERROR, $err);
        }
        $mail->reset();

        return $sent;
    }
}

return 'TwoFactorXEmailSecretProcessor';
