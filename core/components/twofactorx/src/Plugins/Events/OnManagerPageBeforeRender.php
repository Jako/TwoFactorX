<?php
/**
 * @package twofactorx
 * @subpackage plugin
 */

namespace TreehillStudio\TwoFactorX\Plugins\Events;

use TreehillStudio\TwoFactorX\Plugins\Plugin;

class OnManagerPageBeforeRender extends Plugin
{
    public function process()
    {
        $options = $this->twofactorx->options;
        unset($options['encryption_key']);

        $assetsUrl = $this->twofactorx->getOption('assetsUrl');
        $jsUrl = $this->twofactorx->getOption('jsUrl') . 'mgr/';
        $jsSourceUrl = $assetsUrl . '../../../source/js/mgr/';
        $cssUrl = $this->twofactorx->getOption('cssUrl') . 'mgr/';
        $cssSourceUrl = $assetsUrl . '../../../source/css/mgr/';

        $this->modx->controller->addLexiconTopic('twofactorx:default');
        if ($this->twofactorx->getOption('debug') && ($this->twofactorx->getOption('assetsUrl') != MODX_ASSETS_URL . 'components/twofactorx/')) {
            $this->modx->controller->addCss($cssSourceUrl . 'twofactorx.css?v=v' . $this->twofactorx->version);
            $this->modx->controller->addJavascript($jsSourceUrl . 'twofactorx.js?v=v' . $this->twofactorx->version);
        } else {
            $this->modx->controller->addCss($cssUrl . 'twofactorx.min.css?v=v' . $this->twofactorx->version);
            $this->modx->controller->addJavascript($jsUrl . 'twofactorx.min.js?v=v' . $this->twofactorx->version);
        }
        $this->modx->controller->addHtml('<script type="text/javascript">
            Ext.onReady(function() {
                TwoFactorX.config = ' . json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ';
            });
        </script>');

        $userid = $this->modx->user->get('id');
        $this->twofactorx->loadUserByID($userid);
        if ($this->twofactorx->userOnetimeStatus && $this->twofactorx->getOption('enable_2fa') && !$this->twofactorx->userStatus) {
            if ($this->twofactorx->getOption('debug') && ($this->twofactorx->getOption('assetsUrl') != MODX_ASSETS_URL . 'components/twofactorx/')) {
                $this->modx->controller->addLastJavascript($jsSourceUrl . 'useronetime.js?v=v' . $this->twofactorx->version);
            } else {
                $this->modx->controller->addLastJavascript($jsUrl . 'useronetime.min.js?v=v' . $this->twofactorx->version);
            }
            $this->twofactorx->resetUserOnetime();
        }

        if ($this->modx->request->action == 'security/profile' && $this->twofactorx->getOption('show_in_profile')) {
            if ($this->twofactorx->getOption('debug') && ($this->twofactorx->getOption('assetsUrl') != MODX_ASSETS_URL . 'components/twofactorx/')) {
                $this->modx->controller->addLastJavascript($jsSourceUrl . 'userprofile.js?v=v' . $this->twofactorx->version);
            } else {
                $this->modx->controller->addLastJavascript($jsUrl . 'userprofile.min.js?v=v' . $this->twofactorx->version);
            }
        }
    }
}
