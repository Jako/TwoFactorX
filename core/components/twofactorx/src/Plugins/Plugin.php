<?php
/**
 * Abstract plugin
 *
 * @package twofactorx
 * @subpackage plugin
 */

namespace TreehillStudio\TwoFactorX\Plugins;

use modX;
use TreehillStudio\TwoFactorX\TwoFactorX;

/**
 * Class Plugin
 */
abstract class Plugin
{
    /** @var modX $modx */
    protected $modx;
    /** @var TwoFactorX $twofactorx */
    protected $twofactorx;
    /** @var array $scriptProperties */
    protected $scriptProperties;

    /**
     * Plugin constructor.
     *
     * @param $modx
     * @param $scriptProperties
     */
    public function __construct($modx, &$scriptProperties)
    {
        $this->scriptProperties = &$scriptProperties;
        $this->modx =& $modx;
        $corePath = $this->modx->getOption('twofactorx.core_path', null, $this->modx->getOption('core_path') . 'components/twofactorx/');
        $this->twofactorx = $this->modx->getService('twofactorx', 'TwoFactorX', $corePath . 'model/twofactorx/', [
            'core_path' => $corePath
        ]);
    }

    /**
     * Run the plugin event.
     */
    public function run()
    {
        $init = $this->init();
        if ($init !== true) {
            return;
        }

        $this->process();
    }

    /**
     * Initialize the plugin event.
     *
     * @return bool
     */
    public function init()
    {
        return true;
    }

    /**
     * Process the plugin event code.
     *
     * @return mixed
     */
    abstract public function process();
}
