<?php
/**
 * @package twofactorx
 * @subpackage plugin
 */

namespace TreehillStudio\TwoFactorX\Plugins\Events;

use TreehillStudio\TwoFactorX\Plugins\Plugin;
use xPDO;

class OnBeforeManagerLogin extends Plugin
{
    /**
     * {@inheritDoc}
     * @return void
     */
    public function process()
    {
        $username = $_POST['username'];
        $code = $_POST['code'];

        $this->twofactorx->loadUserByName($username);

        if ($this->twofactorx->getOption('enable_2fa')) {
            $this->modx->controller->addLexiconTopic('twofactorx:default');
            if (!$this->twofactorx->getUserExist()) {
                $output = true;
            } else if ($this->twofactorx->getUserTotpStatus()) {
                $output = true;
                if ($this->twofactorx->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Two Factor Authentication for user $username is disabled.");
                }
            } else if ($this->twofactorx->getUserVerifyTotpStatus()) {
                $output = true;
                if ($this->twofactorx->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "User $username logged in verify TOTP mode.");
                }
            } else if (empty($code)) {
                $output = $this->modx->lexicon('twofactorx.enterkey');
            } else if (preg_match("/^[0-9]{6}$/", $code) < 1) {
                $output = $this->modx->lexicon('twofactorx.error_invalid_format');
            } else if ($this->twofactorx->userCodeMatch($code)) {
                $output = true;
            } else {
                $output = $this->modx->lexicon('twofactorx.error_invalid_key');
            }
        } else {
            $output = true;
            if ($this->twofactorx->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "TwoFactorX is disabled.");
            }
        }
        $this->modx->event->_output = $output;
    }
}
