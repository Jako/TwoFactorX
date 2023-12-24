<?php
/**
 * @package twofactorx
 * @subpackage plugin
 */

namespace TreehillStudio\TwoFactorX\Plugins\Events;

use TreehillStudio\TwoFactorX\Plugins\Plugin;

class OnUserFormSave extends Plugin
{
    /**
     * {@inheritDoc}
     * @return void
     */
    public function process()
    {
        if ($this->scriptProperties['mode'] == 'new') {
            $user = $this->scriptProperties['user'];
            $userid = $user->get('id');
            $this->twofactorx->loadUserByID($userid);
        }
    }
}
