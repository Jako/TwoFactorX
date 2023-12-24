<?php
/**
 * Abstract processor
 *
 * @package twofactorx
 * @subpackage processors
 */

namespace TreehillStudio\TwoFactorX\Processors;

use TreehillStudio\TwoFactorX\TwoFactorX;
use modProcessor;
use modX;

/**
 * Class Processor
 */
abstract class Processor extends modProcessor
{
    public $languageTopics = ['twofactorx:default'];

    /** @var TwoFactorX */
    public $twofactorx;

    /**
     * {@inheritDoc}
     * @param modX $modx A reference to the modX instance
     * @param array $properties An array of properties
     */
    function __construct(modX &$modx, array $properties = [])
    {
        parent::__construct($modx, $properties);

        $corePath = $this->modx->getOption('twofactorx.core_path', null, $this->modx->getOption('core_path') . 'components/twofactorx/');
        $this->twofactorx = $this->modx->getService('agenda', TwoFactorX::class, $corePath . 'model/twofactorx/');
    }

    abstract public function process();

    /**
     * Get a boolean property.
     * @param string $k
     * @param mixed $default
     * @return bool
     */
    public function getBooleanProperty($k, $default = null)
    {
        return ($this->getProperty($k, $default) === 'true' || $this->getProperty($k, $default) === true || $this->getProperty($k, $default) === '1' || $this->getProperty($k, $default) === 1);
    }
}
