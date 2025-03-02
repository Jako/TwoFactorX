<?php
/**
 * TwoFactorX
 *
 * Copyright 2014-2023 by Mina Gerges <gerges.mina@gmail.com>
 * Copyright 2023-2025 by Thomas Jakobi <office@treehillstudio.com>
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
    public string $version = '1.0.8';

    /**
     * The class options
     * @var array $options
     */
    public $options = [];

    public bool $userStatus = false;
    public bool $userExist = false;
    public bool $userOnetimeStatus = false;
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
    /** @var int $cipherOptions */
    private int $cipherOptions = OPENSSL_RAW_DATA & OPENSSL_ZERO_PADDING;
    /** @var string $encryptionKey */
    private $encryptionKey;
    /**  @var string $userIV */
    private string $userIV;

    /**
     * TwoFactorX constructor
     *
     * @param modX $modx A reference to the modX instance.
     * @param array $options An array of options. Optional.
     */
    public function __construct(modX &$modx, array $options = [])
    {
        $this->modx =& $modx;
        $this->namespace = $this->getOption('namespace', $options, $this->namespace);

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
            'encryption_key' => $this->modx->getOption($this->namespace . '.encryption_key', null, $this->modx->lexicon('agenda.manager_date_format')),
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
    public function isUserDisabled(): bool
    {
        return (bool)$this->userSettings['totp_disabled'];
    }

    /**
     * @param $code
     * @return bool
     */
    public function userCodeMatch($code): bool
    {
        $secret = $this->userSettings['secret'];
        if ($secret) { // Secret found
            $otp = $this->ga->getCode($secret); // Recalculated for logging
            if ($this->ga->verifyCode($secret, $code, 2)) {
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
     */
    public function resetSecret()
    {
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
            $this->userExist = true;
            $this->userName = $this->user->get('username');
            $this->userId = $userid;
            $this->getUserSettings();
            return true;
        } else {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "No user was found with ID: $userid", '', 'TwoFactorX');
            }
            $this->userName = '';
            $this->userId = 0;
            $this->user = null;
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
            $this->userExist = true;
            $this->userId = $this->user->get('id');
            $this->userName = $username;
            $this->getUserSettings();
            return true;
        } else {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "No user was found with name: $username", '', 'TwoFactorX');
            }
            return false;
        }
    }

    /**
     * @return array
     */
    public function getDecryptedSettings(): array
    {
        $settings = $this->userSettings;
        $settings['totp_disabled'] = $this->userStatus;
        return $settings;
    }

    /**
     * Populate TwoFactorX extended field to $this->userSettings array
     */
    private function getUserSettings()
    {
        $profile = $this->user->getOne('Profile');
        $extended = $profile->get('extended');
        $userSettings = null;
        if (is_array($extended) && isset($extended['twofactorx']) && is_array($extended['twofactorx'])) {
            $userSettings = $extended['twofactorx'];
        }
        if (is_array($userSettings)) { // extended field container in place, we load settings.
            $this->userIV = base64_decode($userSettings['iv']);
            // Validate IV to avoid php warning
            if (strlen(bin2hex($this->userIV)) / 2 != 16) {
                $this->userIV = $this->generateIV();
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Invalid stored IV, for user: $this->userName ($this->userId)", '', 'TwoFactorX');
                }
            }
            $this->userSettings = $this->getDecryptedArray($userSettings);
            $this->userSettings['inonetime'] = preg_replace('/[^[:print:]]/', '', $this->userSettings['inonetime']); // Fix issue with decrypted string
            if (!$this->isSecretValid($this->userSettings['secret'])) {
                $this->resetSecret();
                if ($this->getOption('debug')) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Invalid secret for user: $this->userName ($this->userId)", '', 'TwoFactorX');
                }
            }
            $this->userStatus = $this->getUserStatus();
            $this->userOnetimeStatus = $this->getUserOnetimeStatus();
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
        if ($this->user) {
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
                'inonetime' => $this->isOnetimeEnabled() ? 'yes' : 'no',
                'secret' => $secret,
                'uri' => $uri,
                'iv' => base64_encode($this->userIV),
            ];
            $this->userStatus = $this->getUserStatus();
            $this->userOnetimeStatus = $this->getUserOnetimeStatus();
        }
    }

    /**
     * @return false|mixed
     */
    private function getUserStatus()
    {
        $usersettings = $this->user->getSettings();
        if (isset($usersettings['totp_disabled'])) {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "User setting totp_disabled loaded for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            return $usersettings['totp_disabled'];
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    private function isOnetimeEnabled()
    {
        $enabled = $this->getOption('enable_onetime');
        $usersettings = $this->user->getSettings();
        if (isset($usersettings['enable_onetime'])) {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "User setting enable_onetime loaded with value {$usersettings['enable_onetime']} for user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            return $usersettings['enable_onetime'];
        } else {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Applying global onetime log in value: $enabled", '', 'TwoFactorX');
            }
            return $enabled;
        }
    }

    /**
     * @return bool
     */
    private function getUserOnetimeStatus(): bool
    {
        if ($this->isOnetimeEnabled() && $this->userSettings['inonetime'] == 'yes') {
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "User is in onetime mode - user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return void
     */
    public function resetUserOnetime()
    {
        if ($this->getOption('debug')) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Resetting user onetime status for user: $this->userName ($this->userId)", '', 'TwoFactorX');
        }
        $this->userSettings['inonetime'] = 'no';
        $this->userOnetimeStatus = false;
        $this->saveUserSettings();
    }

    /**
     * @param bool $status
     * @return void
     */
    public function SetUserDisabledStatus(bool $status = false)
    {
        $userid = $this->user->get('id');
        $setting = $this->modx->getObject('modUserSetting', [
            'user' => $userid,
            'key' => 'totp_disabled'
        ]);
        if ($setting === null && $status) { //no user setting but status is true(GA disabled) then we create
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
        } else if ($setting !== null && $setting->get('value') != $status) { //user setting exists but status changing we just change it
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, "Changing totp_disabled userSetting to $status - user: $this->userName ($this->userId)", '', 'TwoFactorX');
            }
            $setting->set('value', $status);
            $setting->save();
        }
        $this->userStatus = $status;
    }

    /**
     * @return array
     */
    private function getEncryptedArray(): array
    {
        return [
            'inonetime' => $this->encrypt($this->userSettings['inonetime']),
            'secret' => $this->encrypt($this->userSettings['secret']),
            'uri' => $this->encrypt($this->userSettings['uri']),
            'iv' => base64_encode($this->userIV),
        ];
    }

    /**
     * @param $array
     * @return array
     */
    private function getDecryptedArray($array): array
    {
        return [
            'inonetime' => $this->decrypt($this->getOption('inonetime', $array, '')),
            'secret' => $this->decrypt($this->getOption('secret', $array, '')),
            'uri' => $this->decrypt($this->getOption('uri', $array, '')),
            'iv' => $this->getOption('iv', $array, ''),
        ];
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
        if (!$setting) {
            $setting = $this->modx->newObject('modSystemSetting');
            $setting->set('key', 'twofactorx.encryption_key');
            $setting->fromArray([
                'value' => bin2hex(openssl_random_pseudo_bytes(32)),
                'xtype' => 'text-password',
                'namespace' => 'twofactorx',
                'area' => 'system'
            ]);
            $setting->save();
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Created encryption key in system settings!', '', 'TwoFactorX');
            }
        }
        if (!preg_match('/^[0-9A-Fa-f]{64}$/', $setting->get('value'))) {
            $setting->set('value', bin2hex(openssl_random_pseudo_bytes(32)));
            $setting->save();
            if ($this->getOption('debug')) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Invalid encryption key in system settings! Value was reset.', '', 'TwoFactorX');
            }
        }
        $this->encryptionKey = $setting->get('value');
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
