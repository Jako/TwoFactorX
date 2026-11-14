<?php
/**
 * QR Code Get
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

/**
 * Class TwoFactorXQRCodeGetProcessor
 */
class TwoFactorXQRCodeGetProcessor extends Processor
{
    /**
     * Get the QR Code image of the current user or, with the twofactorx_edit permission, of another user.
     *
     * The QR code is built from the stored secret, so the secret is not part of the image URL.
     *
     * @return array|string
     */
    public function process()
    {
        $currentUserId = (int)$this->modx->user->get('id');
        $userId = (int)$this->getProperty('id', 0);
        $canEdit = $this->modx->hasPermission('twofactorx_edit');

        if (!$userId) {
            $userId = $currentUserId;
        }
        if ($userId !== $currentUserId && !$canEdit) {
            return $this->failure($this->modx->lexicon('permission_denied'));
        }
        if (!$this->twofactorx->loadUserByID($userId)) {
            return $this->failure($this->modx->lexicon('twofactorx.error_user_not_found'));
        }

        $settings = $this->twofactorx->getDecryptedSettings();
        // The own QR code is only shown in the profile or in the verify TOTP mode, like in mgr/user/getqrcode
        if (!$canEdit && !$this->twofactorx->getOption('show_in_profile') && ($settings['verifytotp'] ?? '') !== 'yes') {
            return $this->failure($this->modx->lexicon('permission_denied'));
        }
        if (empty($settings['secret'])) {
            return $this->failure($this->modx->lexicon('twofactorx.error_invalid_data'));
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($this->twofactorx->getUri($this->twofactorx->userName, $settings['secret'], $this->twofactorx->getOption('issuer')))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->size(400)
            ->margin(0)
            ->roundBlockSizeMode(new RoundBlockSizeModeNone())
            ->build();

        header('Content-Type: ' . $result->getMimeType());
        header('Cache-Control: no-store');
        @session_write_close();
        exit ($result->getString());
    }
}

return 'TwoFactorXQRCodeGetProcessor';
