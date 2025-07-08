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
     * Get the QR Code image.
     *
     * @return void
     */
    public function process()
    {
        $accountname = $this->getProperty('accountname');
        $secret = $this->getProperty('secret');
        $issuer = $this->getProperty('issuer');

        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($this->twofactorx->getUri($accountname, $secret, $issuer))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->size(400)
            ->margin(0)
            ->roundBlockSizeMode(new RoundBlockSizeModeNone())
            ->build();

        header('Content-Type: ' . $result->getMimeType());
        if ($this->getProperty('download')) {
            header('Content-Disposition: attachment; filename="' . $this->getProperty('guid') . '.svg"');
        }
        @session_write_close();
        exit ($result->getString());
    }
}

return 'TwoFactorXQRCodeGetProcessor';
