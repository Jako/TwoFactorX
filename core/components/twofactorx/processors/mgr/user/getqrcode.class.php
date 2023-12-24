<?php
/**
 * User Get QR Code
 *
 * @package twofactorx
 * @subpackage processors
 */

use TreehillStudio\TwoFactorX\Processors\Processor;

class TwoFactorXUserGetQRCodeProcessor extends Processor
{
    public $permission = 'change_profile';

    public function process()
    {
        $userid = $this->modx->user->get('id');

        $this->twofactorx->loadUserByID($userid);
        $settings = $this->twofactorx->getDecryptedSettings();
        if ($settings) {
            if ($this->twofactorx->getOption('show_in_profile') || $settings['inonetime'] == 'yes') {
                return $this->success('', [
                    'accountname' => $this->twofactorx->userName,
                    'secret' => $settings['secret'],
                    'issuer' => $this->twofactorx->getOption('issuer'),
                ]);
            } else {
                return $this->modx->error->failure($this->modx->lexicon('permission_denied'));
            }
        } else {
            return $this->modx->error->failure($this->modx->lexicon('no_records_found'));
        }
    }
}

return 'TwoFactorXUserGetQRCodeProcessor';
