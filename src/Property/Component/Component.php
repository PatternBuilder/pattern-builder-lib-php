<?php

namespace PatternBuilder\Property\Component;

use JsonSchema;
use PatternBuilder\Property\PropertyInterface;
use PatternBuilder\Property\PropertyAbstract;
use PatternBuilder\Configuration\Configuration;

/**
 * Class to load a schema object.
 */
class Component extends PropertyAbstract implements PropertyInterface
{
    protected $schema;
    protected $property_values;
    protected $schema_name;
    protected $schema_path;

    /**
     * @var \JsonSchema\RefResolver
     */
    protected $resolver;
    /**
     * Twig environmnt object.
     *
     * @var \Twig_Environment
     */
    protected $twig;

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
        parent::__construct($schema, $configuration);

        if ($schema_name) {
            $this->schema_name = $schema_name;
        }
        if ($schema_path) {
            $this->schema_path = $schema_path;
        }
    }

    /**
     * Initialize the configuration and related native objects.
     *
     * @param Configuration $configuration Optional config object.
     */
    public function initConfiguration(Configuration $configuration = null)
    {
        parent::initConfiguration($configuration);
        if (isset($this->configuration)) {
            $this->twig = $this->configuration->getTwig();
            $this->resolver = $this->configuration->getResolver();
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
     * Determines if the schema property exists.
     *
     * @param string $property_name The property to get the schema definition.
     *
     * @return bool TRUE if the property exists.
     */
    public function schemaPropertyExists($property_name)
    {
        return !empty($this->schema->properties->$property_name);
    }

    /**
     * Get the schema definition for a property.
     *
     * @param string $property_name The property to get the schema definition.
     *
     * @return mixed The properties current definition. Null if the property does not exist.
     */
    public function getSchemaProperty($property_name)
    {
        return empty($this->schema->properties->$property_name) ? null : $this->schema->properties->$property_name;
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
        $property = $this->getSchemaProperty($property_name);
        if (!isset($property)) {
            $this->logger->notice('The property %property is not defined in the JSON schema.', array('%property' => $property_name));
        } else {
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
     * @param object $template_variables Full object/array of all property values. Defaults to the current property values.
     * @param bool   $notify             True to log any validation errors. Defaults to false.
     *
     * @return bool|array True if the values are valid, otherwise an array of errors per JsonSchema\Validator::getErrors().
     */
    public function validate($template_variables = null, $notify = false)
    {
        $validator = $this->getValidator();
        if ($validator) {
            // Expand the schema.
            // TODO: recurse & $value->validate() instead?
            $this->resolver->resolve($this->schema, $this->schema_path);

            // Set to current values if none provided.
            if (!isset($template_variables)) {
                $template_variables = $this->prepareRender();
            }

            if (isset($template_variables)) {
                $validator->check($template_variables, $this->schema);
                if (!$validator->isValid()) {
                    $errors = $validator->getErrors();
                    if (empty($errors)) {
                        // Create a top level schema error since something is wrong.
                        $errors = array(array(
                            'property' => $this->schema_path,
                            'message' => 'The JSON schema failed validation.',
                            'constraint' => null,
                        ));
                    }

                    // Log errors.
                    if ($notify) {
                        foreach ($errors as $error) {
                            $error_keys = array(
                              '%name' => $this->schema_name,
                              '%message' => $error['message'],
                              '%property' => isset($error['property']) ? $error['property'] : 'property',
                              '%constraint' => isset($error['constraint']) ? $error['constraint'] : 'unknown',
                            );

                            $this->logger->notice('Schema Validation: "%message" in schema "%name", property "%property" for constraint %constraint', $error_keys);
                        }
                    }

                    return $errors;
                }
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
    public function get($property_name = null)
    {
        if (!isset($property_name)) {
            return $this->property_values;
        }

        // @todo: This probably needs a refactor.
        // If this is a component itself just return the component.
        if (isset($this->property_values->$property_name)
            && is_object($this->property_values->$property_name)
            && get_class($this) == get_class($this->property_values->$property_name)) {
            return $this->property_values->$property_name;
        }
        // If this property is a component itself, just return it's get() method.
        if (isset($this->property_values->$property_name)
            && is_object($this->property_values->$property_name)
            && $this->property_values->$property_name instanceof PropertyInterface) {
            return $this->property_values->$property_name->get();
        } elseif (isset($this->property_values->$property_name)) {
            return $this->property_values->$property_name;
        }

        return;
    }

    /**
     * Render the object.
     */
    public function render()
    {
        $template_variables = $this->prepareRender();

        if ($this->configuration->developerMode()) {
            $this->validate($template_variables, true);
        }

        // Set template property if the schema does not define it.
        // This must happen after developer validation since the property will
        // not validate.
        if (!isset($template_variables->template) && ($theme = $this->getTheme())) {
            $template_variables->template = $theme;
        }

        // Decode/encode turns the full array/object into an array.
        // Just typecasting to an array does not recursively apply it.
        $template_array = json_decode(json_encode($template_variables), true);

        if (!empty($template_variables->template)) {
            return $this->twig->render($template_variables->template, $template_array);
        } else {
            $log_vars = array('%schema' => '');
            if (isset($this->schema_name)) {
                $log_vars['%schema'] = $this->schema_name;
            } elseif (isset($this->schema_path)) {
                $log_vars['%schema'] = $this->schema_path;
            }
            $this->logger->notice('Cannot render: Missing template property in schema %schema.', $log_vars);
        }
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
}
