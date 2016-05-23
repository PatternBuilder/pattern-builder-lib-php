<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Property;

use JsonSchema;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use PatternBuilder\Factory\ComponentFactory;
use PatternBuilder\Configuration\Configuration;

abstract class PropertyAbstract implements LoggerAwareInterface
{
    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \PatternBuilder\Configuration\Configuration
     */
    protected $configuration;

    /**
     * @var \PatternBuilder\Factory\ComponentFactory
     */
    protected $componentFactory;

    /**
     * @var \JsonSchema\Validator
     */
    protected $validator;

    /**
     * PHP Clone interface.
     *
     * Reset properties and object references.
     *
     * NOTE: This object will not be instanced again, so any additions to
     * __construct() must be added.
     */
    public function __clone()
    {
        $configuration = clone $this->configuration;
        $this->initConfiguration($configuration);
        if (isset($this->validator)) {
            $this->validator = clone $this->validator;
        }
    }

    /**
     * Initialize the configuration and related native objects.
     *
     * @param Configuration $configuration Optional config object.
     */
    public function initConfiguration(Configuration $configuration = null)
    {
        if (isset($configuration)) {
            $this->configuration = $configuration;
        }

        if (isset($this->configuration)) {
            $this->setLogger($this->configuration->getLogger());
            $this->componentFactory = null;
            $this->prepareFactory();
        }
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger instance on the object.
     *
     * @return \Psr\Log\LoggerInterface $logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Prepare an instance of a component factory for usage.
     */
    protected function prepareFactory()
    {
        if (empty($this->componentFactory) && isset($this->configuration)) {
            $this->componentFactory = new ComponentFactory($this->configuration);
        }
    }

    /**
     * Return an instance of the component factory for use.
     *
     * @return ComponentFactory
     */
    public function getFactory()
    {
        $this->prepareFactory();

        return $this->componentFactory;
    }

    /**
     * Return an instance of the component configuration.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Return an instance of the component configuration.
     *
     * @return Configuration
     */
    public function getValidator()
    {
        if (!isset($this->validator) && isset($this->configuration)) {
            // Initialize JSON validator.
            $resolver = $this->configuration->getResolver();
            if ($resolver && ($retriever = $resolver->getUriRetriever())) {
                $check_mode = JsonSchema\Validator::CHECK_MODE_NORMAL;
                $this->validator = new JsonSchema\Validator($check_mode, $retriever);
            }
        }

        return $this->validator;
    }

    /**
     * Determine if a value is empty.
     *
     * @param string $value The value to check.
     *
     * @return bool true if empty, false otherwise.
     */
    public function isEmptyValue($value)
    {
        if (!isset($value)) {
            return true;
        } elseif (is_bool($value)) {
            return false;
        } elseif ($value === 0) {
            return false;
        } elseif (empty($value)) {
            return true;
        } elseif (is_object($value)) {
            if ($value instanceof PropertyInterface) {
                return $value->isEmpty();
            } elseif (get_class($value) == 'stdClass') {
                foreach ($value as $k => $val) {
                    if (!$this->isEmptyValue($val)) {
                        return false;
                    }
                }

                return true;
            }
        } elseif (is_array($value)) {
            foreach ($value as $k => $val) {
                if (!$this->isEmptyValue($val)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if a given property contains data.
     *
     * @param string $property_name The property to check.
     *
     * @return bool true if empty, false otherwise.
     */
    public function isEmpty($property_name = null)
    {
        $value = $this->get($property_name);

        return $this->isEmptyValue($value);
    }
}
