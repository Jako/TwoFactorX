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
$migrateSettingKeys = [
    'gax_disabled::invert' => 'twofactorx.enable_2fa',
    'gax_profile_enabled' => 'twofactorx.show_in_profile',
    'gax_issuer' => 'twofactorx.issuer',
    'gax_encrypt_key' => 'twofactorx.encryption_key',
];

/**
 * @param xPDO $modx
 * @param $settingKeys
 * @return bool
 */
$migrateSettings = function ($modx, $settingKeys) {
    foreach ($settingKeys as $settingKey => $settingValue) {
        $settingKey = explode('::', $settingKey, 2);
        /** @var modSystemSetting $oldSetting */
        $oldSetting = $modx->getObject('modSystemSetting', [
            'key' => $settingKey[0]
        ]);
        if ($oldSetting) {
            /** @var modSystemSetting $newSetting */
            $newSetting = $modx->getObject('modSystemSetting', [
                'key' => $settingValue
            ]);
            $value = $oldSetting->get('value');
            if (isset($settingKey[1])) {
                switch ($settingKey[1]) {
                    case 'invert':
                        // Boolean settings are stored as '1'/'0', older values could be 'true'/'false'
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '0' : '1';
                        break;
                }
            }

            if ($newSetting && $newSetting->get('value') != $value) {
                $newSetting->set('value', $value);
                if ($newSetting->save()) {
                    $modx->log(xPDO::LOG_LEVEL_INFO, 'Migrated ' . $settingKey[0] . ' setting to ' . $settingValue . ' setting.');
                } else {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not migrate ' . $settingKey[0] . ' setting to ' . $settingValue . ' setting.');
                }
            }
        }
    }
};

/**
 * Get a boolean system or user setting value from the database, the settings cache could be outdated.
 *
 * @param xPDO $modx
 * @param string $key
 * @param int $userId Get the user setting, if the user ID is not 0
 * @return bool|null Null if the setting does not exist
 */
$getBooleanSettingValue = function ($modx, string $key, int $userId = 0) {
    $setting = ($userId) ?
        $modx->getObject('modUserSetting', ['user' => $userId, 'key' => $key]) :
        $modx->getObject('modSystemSetting', ['key' => $key]);
    return ($setting) ? filter_var($setting->get('value'), FILTER_VALIDATE_BOOLEAN) : null;
};

/**
 * @param xPDO $modx
 * @return void
 */
$migrateUsers = function ($modx) use ($getBooleanSettingValue) {
    // GoogleAuthenticatorX uses the courtesy mode of a user only if it is enabled in the system setting and,
    // if it exists, in the user setting. TwoFactorX has no such setting, so the verify TOTP mode is disabled
    // for the users without an enabled courtesy mode. The value is encrypted like the other user data.
    $courtesyEnabled = (bool)$getBooleanSettingValue($modx, 'gax_courtesy_enabled');
    $encryptionKey = $modx->getObject('modSystemSetting', ['key' => 'gax_encrypt_key']);
    $encryptionKey = ($encryptionKey) ? $encryptionKey->get('value') : '';
    if (!preg_match('/^[0-9A-Fa-f]{64}$/', $encryptionKey)) {
        $encryptionKey = '';
    }

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
                // The courtesy mode of GoogleAuthenticatorX is the verify TOTP mode of TwoFactorX
                $extended['twofactorx']['verifytotp'] = $extended['GoogleAuthenticatorX']['Settings']['incourtesy'] ?? null;
                $extended['twofactorx']['secret'] = $extended['GoogleAuthenticatorX']['Settings']['secret'] ?? null;
                $extended['twofactorx']['uri'] = $extended['GoogleAuthenticatorX']['Settings']['uri'] ?? null;
                $extended['twofactorx']['iv'] = $extended['GoogleAuthenticatorX']['Settings']['iv'] ?? null;

                $userCourtesy = $getBooleanSettingValue($modx, 'gax_courtesy_enabled', $user->get('id'));
                $userCourtesyEnabled = $courtesyEnabled && ($userCourtesy ?? true);
                if (!$userCourtesyEnabled) {
                    $iv = base64_decode((string)$extended['twofactorx']['iv']);
                    if ($encryptionKey && strlen($iv) === 16) {
                        $extended['twofactorx']['verifytotp'] = openssl_encrypt('no', 'AES-256-CBC', $encryptionKey, 0, $iv);
                    } else {
                        $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not disable the verify TOTP mode of the user ' . $user->get('username') . ', the encryption key or the IV is invalid.');
                    }
                }
                $profile->set('extended', $extended);
                if ($profile->save()) {
                    $modx->log(xPDO::LOG_LEVEL_INFO, 'Migrated the extended fields of the user ' . $user->get('username') . '  from GoogleAuthenticatorX to TwoFactorX.');
                } else {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not migrate the extended fields of the user ' . $user->get('username') . '  from GoogleAuthenticatorX to TwoFactorX.');
                }
            }
        }
    }
};

/**
 * Migrate the gax_disabled user settings to totp_disabled user settings
 *
 * @param xPDO $modx
 * @return void
 */
$migrateUserSettings = function ($modx) {
    /** @var modUserSetting[] $oldSettings */
    $oldSettings = $modx->getIterator('modUserSetting', [
        'key' => 'gax_disabled'
    ]);
    foreach ($oldSettings as $oldSetting) {
        $userId = $oldSetting->get('user');
        // Boolean settings are stored as '1'/'0', older values could be 'true'/'false'
        $value = filter_var($oldSetting->get('value'), FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        // An existing TwoFactorX user setting is not changed
        if ($modx->getCount('modUserSetting', ['user' => $userId, 'key' => 'totp_disabled'])) {
            continue;
        }
        /** @var modUserSetting $newSetting */
        $newSetting = $modx->newObject('modUserSetting');
        $newSetting->fromArray([
            'user' => $userId,
            'key' => 'totp_disabled',
            'value' => $value,
            'xtype' => 'combo-boolean',
            'namespace' => 'twofactorx',
            'area' => 'system',
        ], '', true, true);
        if ($newSetting->save()) {
            $modx->log(xPDO::LOG_LEVEL_INFO, 'Migrated the gax_disabled user setting of the user ' . $userId . ' to the totp_disabled user setting.');
        } else {
            $modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not migrate the gax_disabled user setting of the user ' . $userId . ' to the totp_disabled user setting.');
        }
    }
};

/**
 * @param xPDO $modx
 * @return bool
 */
$checkGoogleAuthenticatorX = function ($modx) {
    /** @var modPlugin $oldPlugin */
    $oldPlugin = $modx->getObject('modPlugin', [
        'name' => 'GoogleAuthenticatorX'
    ]);
    return (bool)$oldPlugin;
};

/**
 * @param xPDO $modx
 * @return bool
 */
$disableGoogleAuthenticatorX = function ($modx) {
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
};

if ($object->xpdo) {
    /** @var xPDO $modx */
    $modx =& $object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            if ($checkGoogleAuthenticatorX($modx)) {
                $migrateSettings($modx, $migrateSettingKeys);
                $migrateUsers($modx);
                $migrateUserSettings($modx);
                $disableGoogleAuthenticatorX($modx);
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
