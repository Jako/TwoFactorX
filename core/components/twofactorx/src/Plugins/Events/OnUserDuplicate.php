<?php
/**
 * @package twofactorx
 * @subpackage plugin
 */

namespace TreehillStudio\TwoFactorX\Plugins\Events;

use TreehillStudio\TwoFactorX\Plugins\Plugin;

class OnUserDuplicate extends Plugin
{
    /**
     * {@inheritDoc}
     * @return void
     */
    public function process()
    {
        $user = $this->scriptProperties['user'];
        $userid = $user->get('id');
        $this->twofactorx->loadUserByID($userid);
        $this->twofactorx->resetSecret();
    }
}
