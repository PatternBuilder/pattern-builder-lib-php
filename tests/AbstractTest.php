<?php

namespace PatternBuilder\Test;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Twig environmnt object.
     *
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Provides a mocked Configuration object.
     */
    public function getConfig()
    {
        $twig = $this->getTwig();
        $retriever = new \JsonSchema\Uri\UriRetriever();
        $logger = new \Psr\Log\NullLogger();
        $retriever = new \JsonSchema\Uri\UriRetriever();
        $resolver = new \JsonSchema\RefResolver($retriever);
        $configuration = new \PatternBuilder\Configuration\Configuration($logger, $twig, $resolver);

        return $configuration;
    }

    /**
     * Provides a mocked Twig_Environment object.
     */
    public function getTwig()
    {
        if (!isset($this->twig)) {
            $template_paths = array(__DIR__.'/api/templates');
            $twig_loader = new \Twig_Loader_Filesystem($template_paths);
            $this->twig = new \Twig_Environment($twig_loader);
        }

        return $this->twig;
    }

    /**
     * Instantiate a component object.
     *
     * @param string $schema_name A schema name.
     */
    public function getComponent($schema_name)
    {
        $schema_filename = $schema_name.'.json';
        $schema_path = 'file://'.__DIR__.'/api/json/'.$filename;
        $schema_text = $this->getJson($schema_filename);
        if (empty($schema_text)) {
            throw new \PHPUnit_Framework_Exception('Schema '.$schema_name.' cannot be loaded');
        }

        $schema = json_decode($schema_text);
        if (empty($schema)) {
            throw new \PHPUnit_Framework_Exception('Schema '.$schema_name.' cannot be decoded');
        }

        $configuration = $this->getConfig();

        return new \PatternBuilder\Property\Component\Component($schema, $configuration, $schema_name, $schema_path);
    }

    /**
     * Load the compenent json from a given filename.
     *
     * @param string $filename The filename to load json from.
     */
    public function getJson($filename)
    {
        $filepath = __DIR__.'/api/json/'.$filename;
        if (file_exists($filepath)) {
            return file_get_contents($filepath);
        }
    }

    /**
     * Helper function to set flat data on a component.
     *
     * @param \PatternBuilder\Property\PropertyInterface $property
     *                                                             The property / component object.
     * @param array                                      $values
     *                                                             An array with keys of omponent property name and values to set.
     */
    public function pbSetComponentValues(\PatternBuilder\Property\PropertyInterface $component, array $values)
    {
        // Initialize the factory to create children.
        $factory_config = $this->getConfig();
        $factory = new \PatternBuilder\Factory\ComponentFactory($factory_config);

        $check_schema_property = method_exists($component, 'getSchemaProperty');
        foreach ($values as $key => $value) {
            $schema_property = null;
            if ($check_schema_property) {
                $schema_property = $component->getSchemaProperty($key);
                if (empty($schema_property)) {
                    continue;
                }
            }

            if (isset($schema_property->items) && is_array($value)) {
                // Create array items.
                foreach ($value as $delta => $item_values) {
                    $item = $factory->create($schema_property->items, null);
                    if ($item) {
                        $child_values = is_array($item_values) ? $item_values : array($item_values);
                        $this->pbSetComponentValues($item, $child_values);
                        $component->set($key, $item);
                    }
                }
            } else {
                // Direct set on component.
                $component->set($key, $value);
            }
        }
    }
}
