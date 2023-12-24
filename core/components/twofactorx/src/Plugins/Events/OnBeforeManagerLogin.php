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
            if (!$this->twofactorx->userExist) {
                $output = true;
            } else if ($this->twofactorx->userStatus) {
                $output = true;
                if ($this->twofactorx->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Authenticator for user $username is disabled.");
                }
            } else if ($this->twofactorx->getOption('enable_onetime') && $this->twofactorx->userOnetimeStatus) {
                $output = true;
                if ($this->twofactorx->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "TwoFactorX: user $username logged in onetime mode.");
                }
            } else if (empty($code)) {
                $output = $this->modx->lexicon('twofactorx.enterkey');
            } else if (preg_match("/^[0-9]{6}$/", $code) < 1) {
                $output = $this->modx->lexicon('twofactorx.invalidformat');
            } else if ($this->twofactorx->userCodeMatch($code)) {
                $output = true;
            } else {
                $output = $this->modx->lexicon('twofactorx.invalidcode');
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
