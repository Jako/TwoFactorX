<?php
/**
 * @package twofactorx
 * @subpackage plugin
 */

namespace TreehillStudio\TwoFactorX\Plugins\Events;

use TreehillStudio\TwoFactorX\Plugins\Plugin;

class OnUserFormPrerender extends Plugin
{
    public function process()
    {
        if ($this->modx->hasPermission('twofactorx_edit')) {
            $assetsUrl = $this->twofactorx->getOption('assetsUrl');
            $jsUrl = $this->twofactorx->getOption('jsUrl') . 'mgr/';
            $jsSourceUrl = $assetsUrl . '../../../source/js/mgr/';

            if ($this->twofactorx->getOption('debug') && ($this->twofactorx->getOption('assetsUrl') != MODX_ASSETS_URL . 'components/twofactorx/')) {
                $this->modx->controller->addLastJavascript($jsSourceUrl . 'usertab.js?v=v' . $this->twofactorx->version);
            } else {
                $this->modx->controller->addLastJavascript($jsUrl . 'usertab.min.js?v=v' . $this->twofactorx->version);
            }
        }
    }
}
