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

        if (!$this->twofactorx->loadUserByID($userid)) {
            return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_user_not_found'));
        }
        $settings = $this->twofactorx->getDecryptedSettings();
        if ($settings) {
            if ($this->twofactorx->getOption('show_in_profile') || $settings['verifytotp'] == 'yes') {
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
