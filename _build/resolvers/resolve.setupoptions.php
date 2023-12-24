<?php
/**
 * Resolve setup options
 *
 * @package twofactorx
 * @subpackage build
 *
 * @var array $options
 * @var xPDOObject $object
 */

$success = true;

function meetsRequirements()
{
    $result = true;
    if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
        $result = false;
    }
    return $result;
}

function setEncrptKeySetting()
{
    global $modx;
    $setting = $modx->getObject('modSystemSetting', [
        'key' => 'twofactorx.encryption_key'
    ]);
    if (!$setting) {
        $modx->log(modX::LOG_LEVEL_WARN, "Generating encryption key!");
        $setting = $modx->newObject('modSystemSetting');
        $setting->set('key', 'twofactorx.encryption_key');
        $setting->fromArray([
            'value' => bin2hex(openssl_random_pseudo_bytes(32)),
            'xtype' => 'text-password',
            'namespace' => 'twofactorx',
            'area' => 'system'
        ]);
        $setting->save();
    } else if (!preg_match('/^[0-9A-Fa-f]{64}$/', $setting->get('value'))) {
        $modx->log(modX::LOG_LEVEL_ERROR, "Invalid encryption key in system setting, regenerating key!");
        $setting->set('value', bin2hex(openssl_random_pseudo_bytes(32)));
        $setting->save();
    }
}

/**
 * Determine if the user attributes satisfy an object policy
 *
 * @param array|string $criteria An associative array providing a key and value to
 * search for within the matched policy attributes between policy and
 * principal, or the name of a permission to check.
 * @param modAccessibleObject $target A modAccessibleObject class to limit the check.
 * @param modUser $user
 * @return boolean
 **/
function checkPolicy($criteria, $target, $user)
{
    if (!$user) {
        return false;
    }
    if ($user->get('sudo')) {
        return true;
    }
    if (is_scalar($criteria)) {
        $criteria = ["$criteria" => true];
    }
    $policy = $target->findPolicy();
    if ($policy) {
        $principal = $user->getAttributes($target);
        if (!empty($principal)) {
            foreach ($policy as $policyAccess => $access) {
                foreach ($access as $targetId => $targetPolicy) {
                    foreach ($targetPolicy as $applicablePolicy) {
                        if (isset($principal[$policyAccess][$targetId]) && is_array($principal[$policyAccess][$targetId])) {
                            foreach ($principal[$policyAccess][$targetId] as $acl) {
                                $principalAuthority = intval($acl['authority']);
                                $principalPolicyData = $acl['policy'];
                                $principalId = $acl['principal'];
                                if ($applicablePolicy['principal'] == $principalId) {
                                    if ($principalAuthority <= $applicablePolicy['authority']) {
                                        if (!$applicablePolicy['policy']) {
                                            return true;
                                        }
                                        if (empty($principalPolicyData)) {
                                            $principalPolicyData = [];
                                        }
                                        $matches = array_intersect_assoc($principalPolicyData, $applicablePolicy['policy']);
                                        if ($matches) {
                                            $matched = array_diff_assoc($criteria, $matches);
                                            if (empty($matched)) {
                                                return true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
}

if ($object->xpdo) {
    /** @var xPDO $modx */
    $modx = &$object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            if (!meetsRequirements()) {
                $modx->log(modX::LOG_LEVEL_ERROR, 'Installation requirements not met: OpenSSL encryption/decryption failed.');
                $success = false;
                break;
            }
            setEncrptKeySetting();

            if ($options['notify_by_email']) { /* Email all manager users */
                $modx->getService('lexicon', 'modLexicon');
                $modx->log(modX::LOG_LEVEL_WARN, 'Start sending emails to users with manager access ...');
                /** @var modContext $mgrContext */
                $mgrContext = $modx->getObject('modContext', [
                    'key' => 'mgr'
                ]);
                /** @var modUser[] $users */
                $users = $modx->getCollection('modUser');
                foreach ($users as $user) {
                    if (checkPolicy('frames', $mgrContext, $user)) {
                        // Get body and subject for each user manager language
                        $mgrLanguage = $user->getOption('manager_language', [], 'en');
                        $modx->lexicon->load('twofactorx:email', $mgrLanguage);
                        $subject = $modx->lexicon('twofactorx.notifyemail_subject');
                        $body = $modx->lexicon('twofactorx.notifyemail_body', [
                            'username' => $user->get('username'),
                        ]);
                        $body = '<html><body>' . $body . '</body></html>';
                        if ($user->sendEmail($body, [
                            'subject' => $subject
                        ])) {
                            $modx->log(modX::LOG_LEVEL_INFO, "Email sent to user: {$user->get('username')} ({$user->get('id')})");
                        } else {
                            $modx->log(modX::LOG_LEVEL_WARN, "Sending email to user failed: {$user->get('username')} ({$user->get('id')})");
                        }
                    }
                }
            }
            if ($options['enable_2fa']) {
                $setting = $modx->getObject('modSystemSetting', 'twofactorx.enable_2fa');
                if ($setting) {
                    $setting->set('value', 1);
                    $setting->save();
                    $modx->log(xPDO::LOG_LEVEL_WARN, 'Two-factor authentication enabled.');
                    $modx->cacheManager->refresh([
                        'system_settings' => []
                    ]);
                    $modx->log(xPDO::LOG_LEVEL_INFO, 'Refreshing system settings cache ...');
                }
            }

            $success = true;
            break;

        case xPDOTransport::ACTION_UPGRADE:
            if (!meetsRequirements()) {
                $modx->log(modX::LOG_LEVEL_ERROR, 'Installation requirements not met: OpenSSL encryption/decryption failed.');
                $success = false;
                break;
            }
            setEncrptKeySetting();

            $success = true;
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            $success = true;
            break;
    }
    return $success;
}
