<?php
/**
 * TwoFactorX Plugin
 *
 * @package twofactorx
 * @subpackage plugin
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

use TreehillStudio\TwoFactorX\TwoFactorX;

$className = 'TreehillStudio\TwoFactorX\Plugins\Events\\' . $modx->event->name;

$corePath = $modx->getOption('twofactorx.core_path', null, $modx->getOption('core_path') . 'components/twofactorx/');
/** @var TwoFactorX $twofactorx */
$twofactorx = $modx->getService('twofactorx', 'TwoFactorX', $corePath . 'model/twofactorx/', [
    'core_path' => $corePath
]);

if ($twofactorx) {
    if (class_exists($className)) {
        $handler = new $className($modx, $scriptProperties);
        if (get_class($handler) == $className) {
            $handler->run();
        } else {
            $modx->log(xPDO::LOG_LEVEL_ERROR, $className. ' could not be initialized!', '', 'TwoFactorX Plugin');
        }
    } else {
        $modx->log(xPDO::LOG_LEVEL_ERROR, $className. ' was not found!', '', 'TwoFactorX Plugin');
    }
}

return;
