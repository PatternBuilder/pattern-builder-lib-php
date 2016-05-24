<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Test;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Twig environment object.
     *
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Developer mode: true to enable validation before rendering.
     *
     * @var bool
     */
    protected $developer_mode = false;

    /**
     * The directory containing the JSON schemas.
     *
     * @return string The directory.
     */
    public function getSchemaDir()
    {
        return __DIR__.'/api/json';
    }

    /**
     * The directory containing the TWIG templates.
     *
     * @return string The directory.
     */
    public function getTemplateDir()
    {
        return __DIR__.'/api/templates';
    }

    /**
     * Get the developer mode.
     *
     * @return bool The current value.
     */
    public function getDeveloperMode()
    {
        return $this->developer_mode;
    }

    /**
     * Set the developer mode.
     *
     * @param bool True to enable the developer mode.
     */
    public function setDeveloperMode($enabled)
    {
        $this->developer_mode = $enabled;
    }

    /**
     * Provides a Configuration object.
     *
     * @param bool $developer_mode Set to true to enable the developer mode with validation before rendering.
     *
     * @return \PatternBuilder\Configuration\Configuration The created configuration object.
     */
    public function getConfig($developer_mode = null)
    {
        $twig = $this->getTwig();
        $logger = new TestLogger();
        $retriever = new \JsonSchema\Uri\UriRetriever();
        $resolver = new \JsonSchema\RefResolver($retriever);

        if (!isset($developer_mode)) {
            $developer_mode = $this->getDeveloperMode();
        }
        $configuration = new \PatternBuilder\Configuration\Configuration($logger, $twig, $resolver, $developer_mode);

        return $configuration;
    }

    /**
     * Provides a Twig_Environment object.
     *
     * @return \Twig_Environment The created Twig environment object.
     */
    public function getTwig()
    {
        if (!isset($this->twig)) {
            $template_paths = array($this->getTemplateDir());
            $twig_loader = new \Twig_Loader_Filesystem($template_paths);
            $this->twig = new \Twig_Environment($twig_loader);
        }

        return $this->twig;
    }

    /**
     * Provides a ComponentFactory object.
     *
     * @return \PatternBuilder\Factory\ComponentFactory The created component factory object.
     */
    public function getComponentFactory()
    {
        $factory_config = $this->getConfig();

        return new \PatternBuilder\Factory\ComponentFactory($factory_config);
    }

    /**
     * Instantiate a component object.
     *
     * @param string $schema_name    A schema name.
     * @param bool   $developer_mode Set to true to enable the developer mode with validation before rendering.
     *
     * @return \PatternBuilder\Property\Component\Component The created component object.
     */
    public function getComponent($schema_name, $developer_mode = null)
    {
        $schema_filename = $schema_name.'.json';
        $schema_path = 'file://'.$this->getSchemaDir().'/'.$schema_filename;
        $schema_text = $this->getJson($schema_filename);
        if (empty($schema_text)) {
            throw new \PHPUnit_Framework_Exception('Schema '.$schema_name.' cannot be loaded');
        }

        $schema = json_decode($schema_text);
        if (empty($schema)) {
            throw new \PHPUnit_Framework_Exception('Schema '.$schema_name.' cannot be decoded');
        }

        $configuration = $this->getConfig($developer_mode);

        return new \PatternBuilder\Property\Component\Component($schema, $configuration, $schema_name, $schema_path);
    }

    /**
     * Load the compenent json from a given filename.
     *
     * @param string $filename The filename to load json from.
     *
     * @return string The contents of the file.
     */
    public function getJson($filename)
    {
        $filepath = $this->getSchemaDir().'/'.$filename;
        if (file_exists($filepath)) {
            return file_get_contents($filepath);
        }
    }

    /**
     * Helper function to set flat data on a component.
     *
     * @param \PatternBuilder\Property\PropertyInterface $component
     *                                                              The property / component object.
     * @param array                                      $values
     *                                                              An array with keys of component property name and values to set.
     */
    public function pbSetComponentValues(\PatternBuilder\Property\PropertyInterface $component, array $values)
    {
        // Initialize the factory to create children.
        if (method_exists($component, 'getFactory')) {
            $factory = $component->getFactory();
        } else {
            $factory = $this->getComponentFactory();
        }

        // Get schema path for factory resolving.
        if (method_exists($component, 'getSchemaPath')) {
            $schema_path = $component->getSchemaPath();
        } else {
            // Fake path for child resolving.
            $schema_path = 'file://'.$this->getSchemaDir().'/__nothing.json';
        }

        $check_schema_property = method_exists($component, 'getSchemaProperty');
        foreach ($values as $key => $value) {
            $schema_property = null;
            if ($check_schema_property) {
                $schema_property = $component->getSchemaProperty($key);
                if (empty($schema_property)) {
                    continue;
                }
            }

            if (isset($schema_property->items->properties) && is_array($value)) {
                // Create array of items based on the item schema.
                $top_delta = key($value);
                if (is_numeric($top_delta)) {
                    $property_values = $value;
                } else {
                    $property_values = array($value);
                }

                foreach ($property_values as $delta => $item_values) {
                    $child_values = is_array($item_values) ? $item_values : array($item_values);
                    $item = $factory->create($schema_property->items, $schema_path);
                    if ($item) {
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
