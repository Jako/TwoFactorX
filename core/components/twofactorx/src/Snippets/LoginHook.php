<?php
/**
 * Login hook
 *
 * @package twofactorx
 * @subpackage hook
 */

namespace TreehillStudio\TwoFactorX\Snippets;

/**
 * Class LoginHook
 */
class LoginHook extends Hook
{
    /**
     * Get default snippet properties.
     *
     * @return array
     */
    public function getDefaultProperties()
    {
        return [
            'errorMsg' => $this->modx->lexicon('twofactorx.error_invalid_key'),
        ];
    }

    /**
     * Execute the hook and return the result.
     *
     * @return bool
     * @throws /Exception
     */
    public function execute()
    {
        $values = $this->hook->getValues();

        $username = $values['username'] ?? '';
        $code = $values['code'] ?? '';

        if (($values['service'] ?? '') != 'login') {
            return true;
        }
        if (empty($username)) {
            // The login snippet refuses the login without a username with its own message
            return true;
        }
        if (!$this->twofactorx->loadUserByName($username)) {
            // The login snippet refuses the login of a not existing user
            return true;
        }
        if ($this->twofactorx->isUserBlocked()) {
            // Don't check the code of a blocked user, the login snippet refuses the login with the block message
            return true;
        }

        // The frontend login has no verification step, so the code is always checked,
        // unless the two-factor authentication is disabled for the user
        $settings = $this->twofactorx->getDecryptedSettings();
        if (!$settings['totp_disabled']) {
            // The hook runs before the password is checked. A matching code is only marked as used and a wrong code
            // is only counted as failed login attempt with the correct password. Otherwise, the code could not be used
            // again after a wrong password and anybody knowing the username could block the user.
            $passwordMatches = $this->twofactorx->userPasswordMatches($values['password'] ?? '');
            if (!$this->twofactorx->isEncryptionKeyValid()) {
                $this->hook->addError('user', $this->modx->lexicon('twofactorx.error_encryption_key'));
            } elseif (empty($code)) {
                $this->hook->addError('code', $this->modx->lexicon('twofactorx.enterkey'));
            } elseif (!$this->twofactorx->userCodeMatch($code, $passwordMatches)) {
                // A wrong code counts as failed login attempt, otherwise the code could be guessed without a limit
                if ($passwordMatches) {
                    $this->twofactorx->registerFailedCode();
                }
                $this->hook->addError('user', $this->getProperty('errorMsg'));
            }
        }

        return !$this->hook->hasErrors();
    }
}
