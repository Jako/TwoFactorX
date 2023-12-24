<?php
/**
 * @package twofactorx
 * @subpackage plugin
 */

namespace TreehillStudio\TwoFactorX\Plugins\Events;

use TreehillStudio\TwoFactorX\Plugins\Plugin;

class OnManagerLoginFormRender extends Plugin
{
    public function process()
    {
        if ($this->twofactorx->getOption('enable_2fa')) {
            $this->modx->controller->addLexiconTopic('twofactorx:default');
            $output = '<div class="x-form-item login-form-item" style="clear:left; padding-top: 10px">'
                . '<label for="modx-login-code">' . $this->modx->lexicon('twofactorx.authkey') . '</label>'
                . '<div class="x-form-element login-form-element">'
                . '<input type="text" name="code" id="modx-login-code" value="" tabindex="2" autocomplete="off" maxlength="6" class="x-form-text x-form-field"/>'
                . '</div>'
                . '</div>';
            $this->modx->event->_output = $output;
        }
    }
}
