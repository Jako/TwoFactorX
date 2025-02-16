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
use Endroid\QrCode\Writer\SvgWriter;
use TreehillStudio\TwoFactorX\Processors\Processor;

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
                ->writer(new SvgWriter())
                ->writerOptions([
                    SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true
                ])
                ->data($this->twofactorx->getUri($settings['accountname'], $settings['secret'], $settings['issuer']))
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
                ->size(200)
                ->margin(0)
                ->roundBlockSizeMode(new RoundBlockSizeModeNone())
                ->build();
            $qrsvg = $qrcode->getString();

            $user = $this->modx->getObject('modUser', $userid);
            $mgrLanguage = $user->getOption('manager_language');
            $mgrLanguage = $mgrLanguage ?: $this->modx->getOption('cultureKey');

            $this->modx->lexicon->load($mgrLanguage . ':twofactorx:email');
            $subject = $this->modx->lexicon('twofactorx.qremail_subject');

            $body = $this->modx->lexicon('twofactorx.qremail_body', [
                'username' => $this->twofactorx->userName,
                'secret' => $settings['secret'],
                'qrsvg' => 'data:image/svg+xml,' . rawurlencode($qrsvg),
            ]);

            $body = '<html><body>' . $body . '</body></html>';

            if (!$user->sendEmail($body, [
                'subject' => $subject
            ])) {
                $errorInfo = $this->modx->mail->mailer->ErrorInfo;
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.email_fail') . $errorInfo);
            }

            return $this->modx->error->success($this->modx->lexicon('twofactorx.email_success'));
        } else {
            return $this->modx->error->failure($this->modx->lexicon('twofactorx.invaliddata'));
        }
    }
}

return 'TwoFactorXEmailSecretProcessor';
