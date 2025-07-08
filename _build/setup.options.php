<?php
/**
 * Setup options
 *
 * @package twofactorx
 * @subpackage build
 *
 * @var modX $modx
 * @var array $options
 */

// Defaults
$defaults = [
    'notify_by_email' => true,
    'enable_2fa' => false,
];

$output = '<style type="text/css">
    #modx-setupoptions-panel { display: none; }
    #modx-setupoptions-form p { margin-bottom: 10px; }
    #modx-setupoptions-form h2 { margin-bottom: 15px; }
</style>';

$values = [];
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
        $output .= '<h2>Install TwoFactorX</h2>
        <p>Thanks for installing TwoFactorX. This open source extra is 
        developed by Treehill Studio - MODX development in Münsterland.</p>

        <p>During the installation, we will collect some statistical data (the
        hostname, the MODX UUID, the PHP version and the MODX version of your
        MODX installation). Your data will be kept confidential and under no
        circumstances be used for promotional purposes or disclosed to third
        parties. We only like to know the usage count of this package.</p>
        
        <p>If you install this package, you are giving us your permission to
        collect, process and use that data for statistical purposes.</p>
        
        <p>Please review the installation options carefully.</p>';

        $output .= '<div style="position: relative">
                        <input type="hidden" name="notify_by_email" value="0">
                        <input type="checkbox" name="notify_by_email" id="notify_by_email" ' . (($defaults['notify_by_email']) ? 'checked' : '') . ' value="1"> 
                        <label for="notify_by_email" style="display: inline;">Notify manager users by email</label>
                        <p class="red"><strong>Caution:</strong> Sending emails to all manager users can lead to a timeout in the installation process, depending on the number of users.</p>
                        <br/>
                        <input type="hidden" name="enable_2fa" value="0">
                        <input type="checkbox" name="enable_2fa" id="enable_2fa" ' . (($defaults['enable_2fa']) ? 'checked' : '') . ' value="1"> 
                        <label for="enable_2fa" style="display: inline;">Enable two-factor authentication</label>
                        <p class="red">If you enable two-factor authentication, you have to verify the TOTP secret for the manager users. When it is verified, the manager user has to use the TOTP key to login into the manager.</p>
                    </div>';
        break;
    case xPDOTransport::ACTION_UPGRADE:
        $output .= '<h2>Upgrade TwoFactorX</h2>
        <p>TwoFactorX will be upgraded. This open source extra is developed by
        Treehill Studio - MODX development in Münsterland.</p>

        <p>During the upgrade, we will collect some statistical data (the
        hostname, the MODX uuid, the PHP version, the MODX version of your
        MODX installation and the previous installed version of this extra
        package). Your data will be kept confidential and under no
        circumstances be used for promotional purposes or disclosed to third
        parties. We only like to know the usage count of this package.</p>

        <p>If you upgrade this package, you are giving us your permission to
        collect, process and use that data for statistical purposes.</p>';
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        break;
}

return $output;
