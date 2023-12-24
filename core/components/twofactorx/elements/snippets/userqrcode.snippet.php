<?php
/**
 * User QR Code snippet
 *
 * @package twofactorx
 * @subpackage hook
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

use TreehillStudio\TwoFactorX\Snippets\UserQRCodeSnippet;

$corePath = $modx->getOption('twofactorx.core_path', null, $modx->getOption('core_path') . 'components/twofactorx/');
/** @var TwoFactorX $twofactorx */
$twofactorx = $modx->getService('twofactorx', TwoFactorX::class, $corePath . 'model/twofactorx/', [
    'core_path' => $corePath
]);

$snippet = new UserQRCodeSnippet($modx, $scriptProperties);
if ($snippet instanceof TreehillStudio\TwoFactorX\Snippets\UserQRCodeSnippet) {
    return $snippet->execute();
}
return 'TreehillStudio\TwoFactorX\Snippets\UserQRCodeSnippet class not found';
