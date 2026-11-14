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
        $username = $this->scriptProperties['username'] ?? '';
        $code = $_POST['code'] ?? '';

        $this->twofactorx->loadUserByName($username);

        if ($this->twofactorx->getOption('enable_2fa')) {
            // The event is fired before MODX checks the password. A matching code is only marked as used and a wrong code
            // is only counted as failed login attempt with the correct password. Otherwise, the code could not be used
            // again after a wrong password and anybody knowing the username could block the user.
            $passwordMatches = $this->twofactorx->userPasswordMatches($this->scriptProperties['password'] ?? '');
            // The event can be fired without a manager controller, i.e. by a login processor called in a connector
            $this->modx->getService('lexicon', 'modLexicon');
            $this->modx->lexicon->load('twofactorx:default');
            if ($this->modx->controller) {
                $this->modx->controller->addLexiconTopic('twofactorx:default');
            }
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
            } else if ($this->twofactorx->isUserBlocked()) {
                // Don't check the code of a blocked user, the MODX login processor refuses the login with the block message
                $output = true;
            } else if (!$this->twofactorx->isEncryptionKeyValid()) {
                $output = $this->modx->lexicon('twofactorx.error_encryption_key');
            } else if (empty($code)) {
                $output = $this->modx->lexicon('twofactorx.enterkey');
            } else if (preg_match("/^[0-9]{6}$/", $code) < 1) {
                $output = $this->modx->lexicon('twofactorx.error_invalid_format');
            } else if ($this->twofactorx->userCodeMatch($code, $passwordMatches)) {
                $output = true;
            } else {
                // A wrong code counts as failed login attempt, otherwise the code could be guessed without a limit
                if ($passwordMatches) {
                    $this->twofactorx->registerFailedCode();
                }
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
