<?php
/**
 * Secret Reset
 *
 * @package twofactorx
 * @subpackage processors
 */

use TreehillStudio\TwoFactorX\Processors\Processor;

class TwoFactorXSecretResetProcessor extends Processor
{
    public $permission = 'twofactorx_edit';

    public function process()
    {
        $userid = $this->getProperty('id');

        $this->twofactorx->loadUserByID($userid);
        $this->twofactorx->resetSecret();

        return $this->modx->error->success($this->modx->lexicon('twofactorx.secret_reset'));
    }
}

return 'TwoFactorXSecretResetProcessor';
