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
            'errorMsg' => $this->modx->lexicon('twofactorx.invalidcode'),
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

        $username = $values['username'];
        $code = $values['code'];

        if ($values['service'] != 'login') {
            return true;
        }
        if (empty($username)) {
            return false;
        }
        $this->twofactorx->loadUserByName($username);

        $settings = $this->twofactorx->getDecryptedSettings();
        if (!$settings['totp_disabled'] && $settings['inonetime'] == 'yes') {
            if (empty($code)) {
                $errorMsg = $this->modx->lexicon('twofactorx.enterkey');
                $this->hook->addError('code', $errorMsg);
            }

            if (!$this->twofactorx->userCodeMatch($code)) {
                $this->hook->addError('user', $this->getProperty('errorMsg'));
            }
        }

        return !$this->hook->hasErrors();
    }
}
