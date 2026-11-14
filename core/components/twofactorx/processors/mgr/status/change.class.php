<?php
/**
 * Status Change
 *
 * @package twofactorx
 * @subpackage processors
 */

use TreehillStudio\TwoFactorX\Processors\Processor;

class TwoFactorXStatusChangeProcessor extends Processor
{
    public $permission = 'twofactorx_edit';

    public function process()
    {
        $userid = $this->getProperty('id');
        $status = $this->getProperty('status');

        // The status is the current status, which is toggled. An unknown status must not disable the 2FA.
        if (!in_array($status, ['ENABLED', 'DISABLED'], true)) {
            return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_invalid_data'));
        }
        if (!$this->twofactorx->loadUserByID($userid)) {
            return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_user_not_found'));
        }
        $this->twofactorx->setUserTotpStatus($status === 'ENABLED');
        return $this->modx->error->success($this->modx->lexicon('twofactorx.status_changed'));
    }
}

return 'TwoFactorXStatusChangeProcessor';
