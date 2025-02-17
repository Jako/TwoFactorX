<?php
/**
 * Email Secret with QR Code as Attachment
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
use MODX\Revolution\Mail\modMail;
use MODX\Revolution\Mail\modPHPMailer;

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
            
            $tempFile = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
            file_put_contents($tempFile, $qrcode->getString());

            $user = $this->modx->getObject('modUser', $userid);
            $mgrLanguage = $user->getOption('manager_language');
            $mgrLanguage = $mgrLanguage ?: $this->modx->getOption('cultureKey');

            $this->modx->lexicon->load($mgrLanguage . ':twofactorx:email');
            $subject = $this->modx->lexicon('twofactorx.qremail_subject');

            $body = $this->modx->lexicon('twofactorx.qremail_body', [
                'username' => $this->twofactorx->userName,
                'secret' => $settings['secret'],
            ]);

            $body = '<html><body>' . $body . '</body></html>';

            $attachments = ['path' => $tempFile, 'name' => 'qrcode.png', 'mime' => 'image/png'];

            if (!$this->sendEmail($body, $user, [
                'subject' => $subject,
                'attachments' => $attachments
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
     * @return void
     */
    public function sendEmail($message, $user, $options = []) {  
         /** @var modUserProfile $profile */
         $profile = $user->getOne('Profile');
         /** @var modPHPMailer $mail */
         $mail = $this->modx->getService('mail', modPHPMailer::class);
 
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
         
         $mail->attach(
            $options['attachments']['path'],
            $options['attachments']['name']
        );

         $mail->setHTML($this->modx->getOption('html', $options, true));
         if (!$sent = $mail->send()) {
             $err = $this->modx->lexicon('error_sending_email_to') . $profile->get('email') . ': ' . $mail->mailer->ErrorInfo;
             $this->modx->log(modx::LOG_LEVEL_ERROR, $err);
         }
         $this->modx->mail->reset();
            
        return $sent;
    }
}

return 'TwoFactorXEmailSecretProcessor';