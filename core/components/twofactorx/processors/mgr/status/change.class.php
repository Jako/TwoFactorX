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

        $current = true;
        if ($status == 'DISABLED') {
            $current = false;
        }
        $this->twofactorx->loadUserByID($userid);
        $this->twofactorx->setUserTotpStatus($current);
        return $this->modx->error->success($this->modx->lexicon('twofactorx.status_changed'));
    }
}

return 'TwoFactorXStatusChangeProcessor';
