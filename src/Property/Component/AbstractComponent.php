<?php

namespace PatternBuilder\Property\Component;

use JsonSchema;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use PatternBuilder\Factory\ComponentFactory;

/**
 * Class to load a schema object.
 */
abstract class AbstractComponent implements LoggerAwareInterface
{
    protected $schema;
    protected $property_values;
    protected $schema_name;
    protected $schema_path;

    /**
     * @var \PatternBuilder\Factory\ComponentFactory
     */
    protected static $componentFactory;
    protected $configuration;
    protected $validator;

    /**
     * @var \JsonSchema\RefResolver
     */
    protected $resolver;

    /**
     * Twig environment object.
     *
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * Constructor for the component.
     *
     * @param object        $schema        A parsed json schema definition.
     * @param Configuration $configuration Config object.
     * @param string        $schema_name   Short name of the schema.
     * @param string        $schema_path   Full path to the schema file.
     */
    public function __construct($schema, $configuration, $schema_name = null, $schema_path = null)
    {
        $this->property_values = new \stdClass();

        if ($schema_name) {
            $this->schema_name = $schema_name;
        }
        if ($schema_path) {
            $this->schema_path = $schema_path;
        }

        $this->schema = $schema;
        $this->setLogger($configuration->getLogger());
        $this->twig = $configuration->getTwig();
        $this->configuration = $configuration;
        $this->validator = new JsonSchema\Validator();
        $retriever = new JsonSchema\Uri\UriRetriever();
        $this->resolver = new JsonSchema\RefResolver($retriever);
        $this->prepareFactory();
    }

    /**
     * Prepare an instance of a component factory for usage.
     */
    protected function prepareFactory()
    {
        if (empty(self::$componentFactory)) {
            self::$componentFactory = new ComponentFactory($this->configuration);
        }
    }

    /**
     * Return an instance of the component factory for use.
     *
     * @return ComponentFactory
     */
    protected function getFactory()
    {
        $this->prepareFactory();

        return self::$componentFactory;
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
     * Get the theme function for this components.
     *
     * @return mixed The twig template for this component, or false if unknown.
     */
    public function getTheme()
    {
        return empty($this->schema_name) ? false : $this->schema_name.'.twig';
    }
}
