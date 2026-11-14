<?php
/**
 * User Verify TOTP
 *
 * @package twofactorx
 * @subpackage processors
 */

use TreehillStudio\TwoFactorX\Processors\Processor;

class TwoFactorXUserVerifyTotpProcessor extends Processor
{
    public function process()
    {
        $userid = $this->modx->user->get('id');
        $key = $this->getProperty('key');

        $this->twofactorx->loadUserByID($userid);
        if (!$this->twofactorx->getOption('enable_2fa')) {
            return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_2fa_disabled'));
        } else {
            if (!$this->twofactorx->getUserExist()) {
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_user_not_found'));
            } else if ($this->twofactorx->getUserTotpStatus()) {
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_user_totp_disabled'));
            } else if ($this->twofactorx->isUserBlocked()) {
                // Too many wrong codes, the code could be guessed otherwise
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_too_many_attempts'));
            } else if (empty($key)) {
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_empty_key'));
            } else if (preg_match("/^[0-9]{6}$/", $key) < 1) {
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_invalid_format'));
            } else if (!$this->twofactorx->getUserVerifyTotpStatus()) {
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_invalid_key'));
            } else if ($this->twofactorx->userCodeMatch($key)) {
                $this->twofactorx->setVerfyTotpStatus('no');
                return $this->modx->error->success();
            } else {
                // A wrong code counts as failed login attempt and blocks the verification like a login
                $this->twofactorx->registerFailedCode();
                if ($this->twofactorx->isUserBlocked()) {
                    return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_too_many_attempts'));
                }
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.error_invalid_key'));
            }
        }
    }
}

return 'TwoFactorXUserVerifyTotpProcessor';
