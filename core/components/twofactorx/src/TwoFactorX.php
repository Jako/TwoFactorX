<?php
/**
 * TwoFactorX
 *
 * Copyright 2014-2023 by Mina Gerges <gerges.mina@gmail.com>
 * Copyright 2023-2026 by Thomas Jakobi <office@treehillstudio.com>
 *
 * @package twofactorx
 * @subpackage classfile
 */

namespace TreehillStudio\TwoFactorX;

use Exception;
use modUser;
use modX;
use xPDO;

class TwoFactorX
{
    /**
     * A reference to the modX instance
     * @var modX $modx
     */
    public modX $modx;

    /**
     * The namespace
     * @var string $namespace
     */
    public $namespace = 'twofactorx';

    /**
     * The package name
     * @var string $packageName
     */
    public string $packageName = 'TwoFactorX';

    /**
     * The version
     * @var string $version
     */
    public string $version = '1.1.5';

    /**
     * The class options
     * @var array $options
     */
    public $options = [];

    public string $userName;
    public int $userId;

    /** @var GoogleAuthenticator $ga */
    public GoogleAuthenticator $ga;

    /** @var modUser $user */
    private $user;

    /** @var array $userSettings */
    private array $userSettings = [];

    /** @var string $cipherMethod */
    private string $cipherMethod = 'AES-256-CBC';
    /**
     * The cipher options, 0 returns base64 encoded data with PKCS#7 padding. The stored data is encrypted with
     * these options, so they must not be changed.
     * @var int $cipherOptions
     */
    private int $cipherOptions = 0;
    /** @var string $encryptionKey */
    private $encryptionKey;
    /**  @var string $userIV */
    private string $userIV;
    /** @var bool $encryptionKeyValid */
    private bool $encryptionKeyValid = true;

    /**
     * TwoFactorX constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, array $options = [])
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/' . $this->namespace . '/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/' . $this->namespace . '/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/' . $this->namespace . '/');
        $modxversion = $this->modx->getVersionData();

        // Load some default paths for easier management
        $this->options = array_merge([
            'namespace' => $this->namespace,
            'version' => $this->version,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'vendorPath' => $corePath . 'vendor/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'pagesPath' => $corePath . 'elements/pages/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'pluginsPath' => $corePath . 'elements/plugins/',
            'controllersPath' => $corePath . 'controllers/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $assetsUrl . 'connector.php'
        ], $options);

        $lexicon = $this->modx->getService('lexicon', 'modLexicon');
        $lexicon->load($this->namespace . ':default');

        $this->packageName = $this->modx->lexicon('twofactorx');

        // Add default options
        $this->options = array_merge($this->options, [
            'debug' => $this->getBooleanOption('debug', [], false),
            'modxversion' => $modxversion['version'],
            'enable_2fa' => $this->getBooleanOption('enable_2fa', [], false),
            'enable_onetime' => $this->getBooleanOption('enable_onetime', [], true),
            'show_in_profile' => $this->getBooleanOption('show_in_profile', [], false),
            'issuer' => $this->modx->getOption($this->namespace . '.issuer', null, $this->modx->getOption('site_name'), true),
            'encryption_key' => $this->modx->getOption($this->namespace . '.encryption_key', null, '*'),
        ]);

        $this->ga = new GoogleAuthenticator();

        $encryptionKey = $this->getOption('encryption_key');
        if ($encryptionKey && preg_match('/^[0-9A-Fa-f]{64}$/', $encryptionKey)) {
            $this->encryptionKey = $encryptionKey;
        } else {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Invalid encryption key, validating global setting ...', '', 'TwoFactorX');
            $this->validateEncryptionKey();
        }

        $this->loadUserByID($this->modx->user->get('id'));
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption(string $key, array $options = [], $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("$this->namespace.$key", $this->modx->config)) {
                $option = $this->modx->getOption("$this->namespace.$key");
            }
        }
        return $option;
    }

    /**
     * Get Boolean Option
     *
     * @param string $key
     * @param array $options
     * @param mixed $default
     * @return bool
     */
    public function getBooleanOption(string $key, array $options = [], $default = null): bool
    {
        $option = $this->getOption($key, $options, $default);
        return ($option === 'true' || $option === true || $option === '1' || $option === 1);
    }

    /**
     * @return bool
     */
    public function isUserTotpDisabled(): bool
    {
        return isset($this->userSettings['totp_disabled']) && $this->userSettings['totp_disabled'];
    }

    /**
     * Does the code match the secret of the user? A code can be used only once, a code of the same or an
     * earlier time slice as the last used code does not match.
     *
     * @param $code
     * @param bool $markUsed Mark the time slice of a matching code as used
     * @return bool
     */
    public function userCodeMatch($code, bool $markUsed = true): bool
    {
        if (!$this->encryptionKeyValid) {
            // The stored secrets can't be decrypted, so no code can match
            return false;
        }
        $secret = $this->userSettings['secret'] ?? '';
        if ($secret) { // Secret found
            $otp = $this->ga->getCode($secret); // Recalculated for logging
            $timeSlice = $this->ga->getMatchingTimeSlice($secret, $code, 2);
            if ($timeSlice !== null && $timeSlice <= (int)($this->userSettings['lastslice'] ?? 0)) {
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Code already used by user: $this->userName ($this->userId)", '', 'TwoFactorX');
                }
                return false;
            } elseif ($timeSlice !== null) {
                if ($markUsed) {
                    $this->userSettings['lastslice'] = $timeSlice;
                    $this->saveUserSettings();
                }
                return true;
            } else {
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Key mismatch user: $this->userName ($this->userId)" . " - entered: $code expected: $otp", '', 'TwoFactorX');
                }
                return false;
            }
        } elseif (!$this->user) { // No user found
            return true;
        } elseif (!$secret || !$this->isSecretValid($secret)) { // Secret is not set or invalid
            $this->resetSecret();
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Missing or invalid secret for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            return false;
        }
        return false;
    }

    /**
     * Is the loaded user blocked, i.e. after too many failed login attempts?
     *
     * @return bool
     */
    public function isUserBlocked(): bool
    {
        if (!$this->user) {
            return false;
        }
        $profile = $this->user->getOne('Profile');
        if (!$profile) {
            return false;
        }
        return $profile->get('blocked') ||
            $profile->get('blockeduntil') > time() ||
            ($profile->get('blockedafter') > 0 && $profile->get('blockedafter') < time());
    }

    /**
     * Does the password match the password of the loaded user?
     *
     * @param string $password
     * @return bool
     */
    public function userPasswordMatches($password): bool
    {
        return $this->user && is_string($password) && $password !== '' && $this->user->passwordMatches($password);
    }

    /**
     * Count a wrong authentication code as failed login attempt and block the user
     * like MODX does after too many failed login attempts.
     *
     * @return void
     */
    public function registerFailedCode()
    {
        if (!$this->user || !$this->encryptionKeyValid) {
            return;
        }
        $profile = $this->user->getOne('Profile');
        if (!$profile) {
            return;
        }
        $failedLoginCount = (int)$profile->get('failedlogincount') + 1;
        $profile->set('failedlogincount', $failedLoginCount);
        if ($failedLoginCount >= (int)$this->modx->getOption('failed_login_attempts', null, 5)) {
            $profile->set('blockeduntil', time() + (60 * (int)$this->modx->getOption('blocked_minutes', null, 60)));
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "User blocked after too many failed authentication codes: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
        }
        $profile->save();
    }

    /**
     */
    public function resetSecret()
    {
        if (!$this->user || !$this->encryptionKeyValid) {
            return;
        }
        $profile = $this->user->getOne('Profile');
        $extended = $profile->get('extended');
        if (isset($extended['twofactorx'])) {
            $extended['twofactorx'] = null;
            $profile->set('extended', $extended);
            $profile->save();
        }
        try {
            $this->createDefaultSettings();
            $this->saveUserSettings();
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Secret reset for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
        } catch (Exception $e) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Could not reset secret for user: $this->userName ($this->userId) message: {$e->getMessage()}", '', 'TwoFactorX');
        }
    }

    /**
     * @param $userid
     * @return bool
     */
    public function loadUserByID($userid): bool
    {
        /** @var modUser $user */
        $user = $this->modx->getObject('modUser', $userid);
        if ($user) {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Loading user by ID: $userid", '', 'TwoFactorX');
            }
            $this->user = $user;
            $this->userName = $this->user->get('username');
            $this->userId = $userid;
            $this->getUserSettings();
            return true;
        } else {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "No user was found with ID: $userid", '', 'TwoFactorX');
            }
            $this->user = null;
            $this->userName = '';
            $this->userId = 0;
            $this->userSettings = [];
            return false;
        }
    }

    /**
     * @param $username
     * @return bool
     */
    public function loadUserByName($username): bool
    {
        /** @var modUser $user */
        $user = $this->modx->getObject('modUser', ['username' => $username]);
        if ($user) {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Loading user by name: $username", '', 'TwoFactorX');
            }
            $this->user = $user;
            $this->userId = $this->user->get('id');
            $this->userName = $username;
            $this->getUserSettings();
            return true;
        } else {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "No user was found with name: $username", '', 'TwoFactorX');
            }
            $this->user = null;
            $this->userName = '';
            $this->userId = 0;
            $this->userSettings = [];
            return false;
        }
    }

    /**
     * @return array
     */
    public function getDecryptedSettings(): array
    {
        $this->userSettings['totp_disabled'] = $this->getUserTotpStatus();
        return $this->userSettings;
    }

    /**
     * Populate TwoFactorX extended field to $this->userSettings array
     */
    private function getUserSettings()
    {
        if (!$this->encryptionKeyValid) {
            // The stored data can't be decrypted. The user is not in the verify TOTP mode,
            // so a login with an enabled two-factor authentication is refused.
            $this->userSettings = [
                'verifytotp' => 'no',
                'secret' => '',
                'uri' => '',
                'iv' => '',
            ];
            $this->userSettings['totp_disabled'] = $this->getUserTotpStatus();
            return;
        }
        $profile = $this->user->getOne('Profile');
        $extended = $profile->get('extended');
        $userSettings = null;
        if (is_array($extended) && isset($extended['twofactorx']) && is_array($extended['twofactorx'])) {
            $userSettings = $extended['twofactorx'];
        }
        if (is_array($userSettings)) { // Extended field container in place, we load settings.
            $this->userIV = base64_decode($userSettings['iv'] ?? '');
            // Validate IV to avoid php warning
            if (strlen(bin2hex($this->userIV)) / 2 != 16) {
                $this->userIV = $this->generateIV();
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Invalid stored IV, for user: $this->userName ($this->userId)", '', 'TwoFactorX');
                }
            }
            $this->userSettings = $this->getDecryptedArray($userSettings);
            if (isset($this->userSettings['inonetime'])) {
                $this->userSettings['verifytotp'] = preg_replace('/[^[:print:]]/', '', $this->userSettings['inonetime']); // Fix issue with decrypted string
                $this->saveUserSettings();
            } else {
                $this->userSettings['verifytotp'] = preg_replace('/[^[:print:]]/', '', $this->userSettings['verifytotp']); // Fix issue with decrypted string
            }
            if (!$this->isSecretValid($this->userSettings['secret'])) {
                $this->resetSecret();
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Invalid secret for user: $this->userName ($this->userId)", '', 'TwoFactorX');
                }
            }
            $this->userSettings['totp_disabled'] = $this->getUserTotpStatus();
            $this->userSettings['uri'] = $this->getUri($this->userName, $this->userSettings['secret'], $this->getOption('issuer'));
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Data loaded for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
        } else { // No setting for the user, we populate all defaults then save
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "No authenticator data was found for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            try {
                $this->createDefaultSettings();
                $this->saveUserSettings();
            } catch (Exception $e) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Could not create default settings for user: $this->userName ($this->userId) - message: {$e->getMessage()}", '', 'TwoFactorX');
            }
        }
    }

    /**
     * @param $secret
     * @return bool
     */
    private function isSecretValid($secret): bool
    {
        $valid = $this->ga->isSecretValid($secret);
        if (!$valid) {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Not a valid secret '$secret' for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            return false;
        }
        return true;
    }

    /**
     * Saves settings array to the extended field
     *
     * @return void
     */
    private function saveUserSettings()
    {
        if ($this->user && $this->encryptionKeyValid) {
            $profile = $this->user->getOne('Profile');
            $extended = $profile->get('extended');
            $extended['twofactorx'] = $this->getEncryptedArray();
            $profile->set('extended', $extended);
            $profile->save();
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Settings saved for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function createDefaultSettings()
    {
        if ($this->user) {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Creating new default settings for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            $username = $this->user->get('username');

            $secret = $this->ga->createSecret();
            $uri = $this->getUri($username, $secret, $this->getOption('issuer'));
            $this->userIV = $this->generateIV();
            $this->userSettings = [
                'verifytotp' => 'yes',
                'secret' => $secret,
                'uri' => $uri,
                'lastslice' => 0,
                'iv' => base64_encode($this->userIV),
            ];
            $this->userSettings['totp_disabled'] = $this->getUserTotpStatus();
        }
    }

    /**
     * @return bool
     */
    public function getUserExist()
    {
        return !empty($this->userId);
    }

    /**
     * @return false|mixed
     */
    public function getUserTotpStatus()
    {
        if (!$this->user) {
            return false;
        }
        $userSettings = $this->user->getSettings();
        $this->userSettings['totp_disabled'] = (isset($userSettings['totp_disabled'])) ? $userSettings['totp_disabled'] === '1' : false;
        if ($this->isUserTotpDisabled()) {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "User setting totp_disabled loaded for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            return $this->userSettings['totp_disabled'];
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function getUserVerifyTotpStatus(): bool
    {
        if ($this->modx->getOption('verifytotp', $this->userSettings) === 'yes') {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "User is in verify TOTP mode - user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return void
     */
    public function setVerfyTotpStatus($status)
    {
        if ($this->getOption('debug')) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Set user verify TOTP status for user: $this->userName ($this->userId) to $status", '', 'TwoFactorX');
        }
        $this->userSettings['verifytotp'] = $status;
        $this->saveUserSettings();
    }

    /**
     * @param bool $status
     * @return void
     */
    public function setUserTotpStatus(bool $status = false)
    {
        if (!$this->user) {
            return;
        }
        $userid = $this->user->get('id');
        $setting = $this->modx->getObject('modUserSetting', [
            'user' => $userid,
            'key' => 'totp_disabled'
        ]);
        if ($setting === null && $status) { // No user setting exist but the TOTP is disabled then create the setting
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Creating totp_disabled userSetting - user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            $setting = $this->modx->newObject('modUserSetting');
            $setting->set('user', $userid);
            $setting->set('key', 'totp_disabled');
            $setting->set('value', $status);
            $setting->set('xtype', 'combo-boolean');
            $setting->set('namespace', 'twofactorx');
            $setting->set('area', 'system');
            $setting->save();
        } else if ($setting !== null && $setting->get('value') != $status) { // User setting exists and the status has changed
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Changing totp_disabled userSetting to $status - user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            $setting->set('value', $status);
            $setting->save();
        }
    }

    /**
     * @return array
     */
    private function getEncryptedArray(): array
    {
        return [
            'verifytotp' => $this->encrypt($this->userSettings['verifytotp']),
            'secret' => $this->encrypt($this->userSettings['secret']),
            'uri' => $this->encrypt($this->userSettings['uri']),
            'lastslice' => $this->encrypt((string)(int)($this->userSettings['lastslice'] ?? 0)),
            'iv' => base64_encode($this->userIV),
        ];
    }

    /**
     * @param $array
     * @return array
     */
    private function getDecryptedArray($array): array
    {
        $result = [
            'verifytotp' => $this->decrypt($this->getOption('verifytotp', $array, '')) === 'no' ? 'no' : 'yes',
            'secret' => $this->decrypt($this->getOption('secret', $array, '')),
            'uri' => $this->decrypt($this->getOption('uri', $array, '')),
            'lastslice' => (int)$this->decrypt($this->getOption('lastslice', $array, '')),
            'iv' => $this->getOption('iv', $array, ''),
        ];
        if ($this->getOption('inonetime', $array, '')) {
            $result['inonetime'] = $this->decrypt($this->getOption('inonetime', $array, ''));
        }
        return $result;
    }

    /**
     * @param $string
     * @return false|string
     */
    private function encrypt($string)
    {
        return openssl_encrypt($string, $this->cipherMethod, $this->encryptionKey, $this->cipherOptions, $this->userIV);
    }

    /**
     * @param $string
     * @return false|string
     */
    private function decrypt($string)
    {
        return openssl_decrypt($string, $this->cipherMethod, $this->encryptionKey, $this->cipherOptions, $this->userIV);
    }

    /**
     * @return void
     */
    private function validateEncryptionKey()
    {
        $setting = $this->modx->getObject('modSystemSetting', [
            'key' => 'twofactorx.encryption_key'
        ]);
        if ($setting && preg_match('/^[0-9A-Fa-f]{64}$/', $setting->get('value'))) {
            // The system settings cache is outdated
            $this->encryptionKey = $setting->get('value');
            return;
        }

        if ($this->hasStoredUserData()) {
            // A new key would make the stored secrets unreadable. They would be reset and all users
            // could log in once without a code. The logins with a code are refused instead.
            $this->encryptionKeyValid = false;
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'The system setting twofactorx.encryption_key is missing or invalid, but users have stored TwoFactorX data. Restore the previous encryption key! Until then, logins that require an authentication code are refused.', '', 'TwoFactorX');
            return;
        }

        if (!$setting) {
            $setting = $this->modx->newObject('modSystemSetting');
            $setting->set('key', 'twofactorx.encryption_key');
            $setting->fromArray([
                'xtype' => 'text-password',
                'namespace' => 'twofactorx',
                'area' => 'system'
            ]);
        }
        $setting->set('value', bin2hex(openssl_random_pseudo_bytes(32)));
        if ($setting->save()) {
            $this->modx->cacheManager->refresh(['system_settings' => []]);
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Generated a new encryption key in the system setting twofactorx.encryption_key.', '', 'TwoFactorX');
        } else {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not save a new encryption key in the system setting twofactorx.encryption_key.', '', 'TwoFactorX');
        }
        $this->encryptionKey = $setting->get('value');
    }

    /**
     * Has any user stored TwoFactorX data, that is encrypted with the encryption key?
     *
     * @return bool
     */
    private function hasStoredUserData(): bool
    {
        return (bool)$this->modx->getCount('modUserProfile', [
            'extended:LIKE' => '%"twofactorx":{%'
        ]);
    }

    /**
     * Can the stored data be encrypted/decrypted with the encryption key?
     *
     * @return bool
     */
    public function isEncryptionKeyValid(): bool
    {
        return $this->encryptionKeyValid;
    }

    /**
     * @return string
     */
    private function generateIV(): string
    {
        $ivlen = openssl_cipher_iv_length($this->cipherMethod);
        return openssl_random_pseudo_bytes($ivlen);
    }

    /**
     * @param $accountname
     * @param $secret
     * @param $issuer
     * @return string
     */
    public function getUri($accountname, $secret, $issuer)
    {
        return 'otpauth://totp/' . urlencode($accountname) . '?secret=' . $secret . '&issuer=' . urlencode($issuer);
    }
}
