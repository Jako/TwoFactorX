<?php
/**
 * User QR Code Snippet
 *
 * @package twofactorx
 * @subpackage snippet
 */

namespace TreehillStudio\TwoFactorX\Snippets;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * Class UserQRCodeSnippet
 */
class UserQRCodeSnippet extends Snippet
{
    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'userid::int' => $this->modx->user->get('id'),
            'placeholderPrefix' => 'twofactorx'
        ];
    }

    /**
     * Execute the snippet and return the result.
     *
     * @return string
     * @throws /Exception
     */
    public function execute()
    {
        $userid = $this->getProperty('userid');

        $output = '';
        if ($userid == 0) {
            return $output;
        }

        $placeholderPrefix = $this->getProperty('placeholderPrefix');
        $placeholderPrefix = ($placeholderPrefix) ? $placeholderPrefix . '.' : '';

        $this->twofactorx->loadUserByID($userid);
        $settings = $this->twofactorx->getDecryptedSettings();
        if ($settings && $this->twofactorx->userName && $settings['secret']) {
            $settings = array_merge($settings, [
                'accountname' => $this->twofactorx->userName,
                'issuer' => $this->twofactorx->getOption('issuer'),
            ]);
            $uri = $this->twofactorx->getUri($settings['accountname'], $settings['secret'], $settings['issuer']);
            $qrcode = Builder::create()
                ->writer(new SvgWriter())
                ->writerOptions([
                    SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true
                ])
                ->data($uri)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
                ->size(200)
                ->margin(0)
                ->roundBlockSizeMode(new RoundBlockSizeModeNone())
                ->build();
            $qrsvg = $qrcode->getString();

            $this->modx->setPlaceholders([
                'secret' => $settings['secret'],
                'uri' => $uri,
                'qrsvg' => $qrsvg,
            ], $placeholderPrefix);
            $output = $qrsvg;
        }
        return $output;
    }
}
