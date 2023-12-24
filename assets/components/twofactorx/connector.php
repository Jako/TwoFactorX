<?php
/**
 * TwoFactorX connector
 *
 * @package twofactorx
 * @subpackage connector
 *
 * @var modX $modx
 */

require_once dirname(__FILE__, 4) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('twofactorx.core_path', null, $modx->getOption('core_path') . 'components/twofactorx/');
/** @var TwoFactorX $twofactorx */
$twofactorx = $modx->getService('twofactorx', TwoFactorX::class, $corePath . 'model/twofactorx/', [
    'core_path' => $corePath
]);

// Handle request
$modx->request->handleRequest([
    'processors_path' => $twofactorx->getOption('processorsPath'),
    'location' => ''
]);
