<?php

namespace PatternBuilder\Property\Component;

use JsonSchema;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use PatternBuilder\Property\PropertyInterface;
use PatternBuilder\Property\PropertyAbstract;
use PatternBuilder\Factory\ComponentFactory;
use PatternBuilder\Configuration\Configuration;

/**
 * Class to load a schema object.
 */
class Component extends PropertyAbstract implements LoggerAwareInterface, PropertyInterface
{
    protected $schema;
    protected $property_values;
    protected $schema_name;
    protected $schema_path;

    protected $configuration;
    protected $validator;
    /**
     * @var \JsonSchema\RefResolver
     */
    protected $resolver;
    /**
     * Twig environmnt object.
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var \PatternBuilder\Factory\ComponentFactory
     */
    protected $componentFactory;

    /**
     * Constructor for the component.
     *
     * @param object        $schema        A parsed json schema definition.
     * @param Configuration $configuration Config object.
     * @param string        $schema_name   Short name of the schema.
     * @param string        $schema_path   Full path to the schema file.
     */
    public function __construct($schema, Configuration $configuration, $schema_name = null, $schema_path = null)
    {
        if ($schema_name) {
            $this->schema_name = $schema_name;
        }
        if ($schema_path) {
            $this->schema_path = $schema_path;
        }

        $this->schema = $schema;

        // Initialize native objects based on the configuration.
        $this->initConfiguration($configuration);

        // Initialize JSON validator.
        $this->validator = new JsonSchema\Validator();

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
        $configuration = clone($this->configuration);
        $this->initConfiguration($configuration);
        $this->validator = clone($this->validator);
    }

    /**
     * Initialize the configuration and related native objects.
     *
     * @param Configuration $configuration Optional config object.
     */
    public function initConfiguration(Configuration $configuration = NULL)
    {
        if (isset($configuration)) {
          $this->configuration = $configuration;
        }

        if (isset($this->configuration)) {
          $this->setLogger($this->configuration->getLogger());
          $this->twig = $this->configuration->getTwig();
          $this->resolver = $this->configuration->getResolver();

          $this->componentFactory = NULL;
          $this->prepareFactory();
        }
    }

    /**
     * Initialize the properties object.
     */
    public function initProperties()
    {
        $this->property_values = new \stdClass();
        $this->initDefaultProperties();
    }

    /**
     * Instantiate any properties which have default values.
     */
    public function initDefaultProperties()
    {
        if (!empty($this->schema->properties)) {
            foreach ($this->schema->properties as $property_name => $property) {
                if (isset($property->default)) {
                    $this->property_values->$property_name = $this->getFactory()->create($property, $this->schema_path);
                }
            }
        }
    }

    /**
     * Set a property value.
     *
     * @param string $property_name The property name to set a value for.
     * @param string $value         The properties new value.
     *
     * @return object $this Component Object.
     */
    public function set($property_name, $value)
    {
        // Ensure the property is defined in the schema JSON.
        if (empty($this->schema->properties->$property_name)) {
            $this->logger->notice('The property %property is not defined in the JSON schema.', array('%property' => $property_name));
        } else {
            $property = $this->schema->properties->$property_name;

            if (!isset($this->property_values->$property_name)) {
                $this->property_values->$property_name = $this->getFactory()->create($property, $this->schema_path);
            }

            if (is_array($value)) {
                foreach ($value as $name => $val) {
                    $this->property_values->$property_name->set($name, $val);
                }
            } else {
                $this->property_values->$property_name->set($property_name, $value);
            }
        }

        return $this;
    }

    /**
     * Validate a properties value.
     *
     * @param object $template_variables Full object/array of all property values.
     *
     * @return bool True if the value is valid, false otherwise.
     */
    private function validate($template_variables)
    {
        $this->resolver->resolve($this->schema, $this->schema_path);
        $this->validator->check($template_variables, $this->schema);
        if (!$this->validator->isValid()) {
            foreach ($this->validator->getErrors() as $error) {
                $error_keys = array(
                  '%name' => $this->schema_name,
                  '%message' => $error['message'],
                );

                $this->logger->notice('%message in %name', $error_keys);
            }
        }

        return true;
    }

    /**
     * Get a properties value.
     *
     * @param string $property_name The property to get the current value for.
     *
     * @return mixed The properties current value. Null if the property does not exist.
     */
    public function get($property_name = NULL)
    {
        if (!isset($property_name)) {
          return $this->property_values;
        }

        // @todo: This probably needs a refactor.
        // If this is a component itself just return the component.
        if (isset($this->property_values->$property_name)
            && is_object($this->property_values->$property_name)
            && get_class($this) == get_class($this->property_values->$property_name))
        {
            return $this->property_values->$property_name;
        }
        // If this property is a component itself, just return it's get() method.
        if (isset($this->property_values->$property_name)
            && is_object($this->property_values->$property_name)
            && $this->property_values->$property_name instanceof PropertyInterface)
        {
            return $this->property_values->$property_name->get();
        }
        else if (isset($this->property_values->$property_name)) {
            return $this->property_values->$property_name;
        }

        return null;
    }

    /**
     * Render the object.
     */
    public function render()
    {
        $template_variables = $this->prepareRender();

        if ($this->configuration->developerMode()) {
            $this->validate($template_variables);
        }
        // Decode/encode turns the full array/object into an array.
        // Just typecasting to an array does not recursively apply it.
        $template_array = json_decode(json_encode($template_variables), true);

        return $this->twig->render($template_variables->template, $template_array);
    }

    /**
     * Prepare this object for rendering.
     */
    public function prepareRender()
    {
        $template_variables = new \stdClass();
        foreach ($this->property_values as $property_name => $value) {
            $template_variables->$property_name = $value->prepareRender();
        }

        if (!isset($template_variables->template) && ($theme = $this->getTheme())) {
            $template_variables->template = $theme;
        }

        return $template_variables;
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
    protected function getFactory()
    {
        $this->prepareFactory();

        return $this->componentFactory;
    }
}
