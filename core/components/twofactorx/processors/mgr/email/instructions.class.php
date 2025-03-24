<?php
/**
 * Email Instructions
 *
 * @package twofactorx
 * @subpackage processors
 */

use TreehillStudio\TwoFactorX\Processors\Processor;

class TwoFactorXEmailInstructionsProcessor extends Processor
{
    public $permission = 'twofactorx_edit';

    public function process()
    {
        $userid = $this->getProperty('id');

        $this->twofactorx->loadUserByID($userid);
        $settings = $this->twofactorx->getDecryptedSettings();
        if ($settings) {
            $user = $this->modx->getObject('modUser', $userid);
            $mgrLanguage = $this->getUserSetting($user->get('id'), 'manager_language');
            $mgrLanguage = $mgrLanguage ??  $this->modx->getOption('cultureKey');
            $this->modx->lexicon->load($mgrLanguage . ':twofactorx:email');
            $subject = $this->modx->lexicon('twofactorx.notifyemail_subject');
            $body = $this->modx->lexicon('twofactorx.notifyemail_body', [
                'username' => $this->twofactorx->userName
            ]);
            $body = '<html><body>' . $body . '</body></html>';
            if (!$user->sendEmail($body, [
                'subject' => $subject
            ])) {
                return $this->modx->error->failure($this->modx->lexicon('twofactorx.email_fail') . $this->modx->mail->mailer->ErrorInfo);
            }
            return $this->modx->error->success($this->modx->lexicon('twofactorx.email_success'));
        } else {
            return $this->modx->error->failure($this->modx->lexicon('twofactorx.invaliddata'));
        }
    }

    /**
     * @param string $userId
     * @param string $settingKey
     * @return string|null
     */
    private function getUserSetting($userId, $settingKey)
    {
        /** @var modUserSetting $userSetting */
        $userSetting = $this->modx->getObject('modUserSetting', [
            'user' => $userId,
            'key' => $settingKey,
        ]);
        return ($userSetting) ? $userSetting->get('value') : null;
    }
}

return 'TwoFactorXEmailInstructionsProcessor';
