<?php
/**
 * User Get Settings
 *
 * @package twofactorx
 * @subpackage processors
 */

use TreehillStudio\TwoFactorX\Processors\Processor;

class TwoFactorXUserGetSettingsProcessor extends Processor
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
            return $this->success('', $settings);
        } else {
            return $this->modx->error->failure($this->modx->lexicon('no_records_found'));
        }
    }
}

return 'TwoFactorXUserGetSettingsProcessor';
