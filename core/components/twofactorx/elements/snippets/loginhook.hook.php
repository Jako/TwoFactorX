<?php
/**
 * Login hook
 *
 * @package twofactorx
 * @subpackage hook
 *
 * @var modX $modx
 * @var array $scriptProperties
 * @var fiHooks $hook
 */

use TreehillStudio\TwoFactorX\Snippets\LoginHook;

$corePath = $modx->getOption('twofactorx.core_path', null, $modx->getOption('core_path') . 'components/twofactorx/');
/** @var TwoFactorX $twofactorx */
$twofactorx = $modx->getService('twofactorx', TwoFactorX::class, $corePath . 'model/twofactorx/', [
    'core_path' => $corePath
]);

$snippet = new LoginHook($modx, $hook, $scriptProperties);
if ($snippet instanceof TreehillStudio\TwoFactorX\Snippets\LoginHook) {
    return $snippet->execute();
}
return 'TreehillStudio\TwoFactorX\Snippets\LoginHook class not found';
