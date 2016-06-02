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
use PatternBuilder\Utility\Inspector;

abstract class PropertyAbstract implements LoggerAwareInterface
{
    /**
     * The JSON schema object.
     *
     * @var object
     */
    protected $schema;

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
     * Constructor for the component.
     *
     * @param object        $schema        A parsed json schema definition.
     * @param Configuration $configuration Config object.
     */
    public function __construct($schema, Configuration $configuration)
    {
        $this->schema = $schema;

        // Initialize native objects based on the configuration.
        $this->initConfiguration($configuration);

        // Initialize properties.
        $this->initProperties();
    }

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
     * Get the schema object.
     *
     * @return object|null The schema object.
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Set the default value for the property schema.
     *
     * @param object $property The property JSON object.
     *
     * @return bool True if the default is set.
     */
    public function setPropertyDefaultValue($property)
    {
        if (isset($property->default)) {
            // Use the defined default.
            return true;
        } elseif (isset($property->enum) && count($property->enum) == 1) {
            // Set default to single enum.
            $property->default = reset($property->enum);

            return true;
        }

        return false;
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
     * Determine if a given property contains data.
     *
     * @param string $property_name The property to check.
     *
     * @return bool true if empty, false otherwise.
     */
    public function isEmpty($property_name = null)
    {
        $value = $this->get($property_name);

        return Inspector::isEmpty($value);
    }
}
