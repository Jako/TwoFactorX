<?php
/**
 * Resolve migrate GoogleAuthenticatorX
 *
 * @package twofactorx
 * @subpackage build
 *
 * @var array $options
 * @var xPDOObject $object
 */

$success = false;
$migrateSettings = [
    'gax_disabled::invert' => 'twofactorx.enable_2fa',
    'gax_courtesy_enabled' => 'twofactorx.enable_onetime',
    'gax_profile_enabled' => 'twofactorx.show_in_profile',
    'gax_issuer' => 'twofactorx.issuer',
    'gax_encrypt_key' => 'twofactorx.encryption_key',
];

/**
 * @param xPDO $modx
 * @param $settingKeys
 * @return bool
 */
function migrateSettings($modx, $settingKeys)
{
    foreach ($settingKeys as $settingKey => $settingValue) {
        $settingKey = explode('::', $settingKey, 1);
        /** @var modSystemSetting $oldSetting */
        $oldSetting = $modx->getObject('modSystemSetting', [
            'key' => $settingKey[0]
        ]);
        if ($oldSetting) {
            /** @var modSystemSetting $newSetting */
            $newSetting = $modx->getObject('modSystemSetting', [
                'key' => $settingValue
            ]);
            if (isset($settingKey[1])) {
                switch ($settingKey[1]) {
                    case 'invert':
                        $value = ($value === 'true') ? 'false' : 'true';
                        break;
                }
            }

            if ($newSetting && $newSetting->get('value') != $oldSetting->get('value')) {
                $newSetting->set('value', $oldSetting->get('value'));
                if ($newSetting->save()) {
                    $modx->log(xPDO::LOG_LEVEL_INFO, 'Migrated ' . $settingKey[0] . ' setting to ' . $settingValue . ' setting.');
                } else {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not migrate ' . $settingKey[0] . ' setting to ' . $settingValue . ' setting.');
                }
            }
        }
    }
}

/**
 * @param xPDO $modx
 * @param $settingKeys
 * @return bool
 */
function migrateUsers($modx)
{
    $c = $modx->newQuery('modUser');
    $c->leftJoin('modUserProfile', 'Profile', ['modUser.id = Profile.internalKey']);
    $c->where(['Profile.extended:LIKE' => '%GoogleAuthenticatorX%']);
    $users = $modx->getIterator('modUser', $c);
    foreach ($users as $user) {
        $profile = $user->getOne('Profile');
        if ($profile) {
            $extended = $profile->get('extended');
            if (is_array($extended) && isset($extended['GoogleAuthenticatorX']) && is_array($extended['GoogleAuthenticatorX'])) {
                $extended['twofactorx'] = [];
                $extended['twofactorx']['incourtesy'] = $extended['GoogleAuthenticatorX']['Settings']['incourtesy'] ?? null;
                $extended['twofactorx']['secret'] = $extended['GoogleAuthenticatorX']['Settings']['secret'] ?? null;
                $extended['twofactorx']['uri'] = $extended['GoogleAuthenticatorX']['Settings']['uri'] ?? null;
                $extended['twofactorx']['iv'] = $extended['GoogleAuthenticatorX']['Settings']['iv'] ?? null;
                $profile->set('extended', $extended);
                if ($profile->save()) {
                    $modx->log(xPDO::LOG_LEVEL_INFO, 'Migrated the extended fields of the user ' . $user->get('username') . '  from GoogleAuthenticatorX to TwoFactorX.');
                } else {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not migrate the extended fields of the user ' . $user->get('username') . '  from GoogleAuthenticatorX to TwoFactorX.');
                }
            }
        }
    }
}

/**
 * @param xPDO $modx
 * @return bool
 */
function checkGoogleAuthenticatorX($modx)
{
    /** @var modPlugin $oldPlugin */
    $oldPlugin = $modx->getObject('modPlugin', [
        'name' => 'GoogleAuthenticatorX'
    ]);
    return (bool)$oldPlugin;
}

/**
 * @param xPDO $modx
 * @return bool
 */
function disableGoogleAuthenticatorX($modx)
{
    /** @var modPlugin $oldPlugin */
    $oldPlugin = $modx->getObject('modPlugin', [
        'name' => 'GoogleAuthenticatorX'
    ]);
    if ($oldPlugin) {
        $oldPlugin->set('disabled', true);
        if ($oldPlugin->save()) {
            $modx->log(xPDO::LOG_LEVEL_INFO, 'GoogleAuthenticatorX plugin disabled. You can uninstall GoogleAuthenticatorX now.');
        } else {
            $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not disable the GoogleAuthenticatorX plugin.');
        }
    }
}

if ($object->xpdo) {
    /** @var xPDO $modx */
    $modx =& $object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            if (checkGoogleAuthenticatorX($modx)) {
                migrateSettings($modx, $migrateSettings);
                migrateUsers($modx);
                disableGoogleAuthenticatorX($modx);
            }
            $success = true;
            break;
        case xPDOTransport::ACTION_UPGRADE:
        case xPDOTransport::ACTION_UNINSTALL:
            $success = true;
            break;
    }
}
return $success;
