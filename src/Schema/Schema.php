<?php

/**
 * Class to load a schema object.
 */

namespace PatternBuilder\Schema;

use PatternBuilder\Exception\SchemaException;
use PatternBuilder\Configuration\Configuration;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class Schema implements LoggerAwareInterface
{
    private $localCache = array();
    private $schemaFiles = array();
    private $configuration;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    private $twig_arguments = array();
    private $use_cache;

    /**
     * Class construct.
     *
     * @param \Psr\Log\LoggerInterface $logger         Logging object
     * @param array                    $twig_arguments Arguments to be passed to the TWIG environment.
     * @param bool                     $use_cache      Used to bypass loading from cache for debugging.
     */
    public function __construct(Configuration $configuration, $twig_arguments = array(), $use_cache = true)
    {
        $this->configuration = $configuration;
        $this->twig_arguments = $twig_arguments;
        $this->use_cache = $use_cache;
        $this->setLogger($configuration->getLogger());

        // Enforce interface so that any class extending this abstract must
        // implement the SchemaInterface.
        if (!($this instanceof SchemaInterface)) {
            throw new SchemaException('Schema class "'.get_class($this).'" does not implement SchemaInterface.');
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
     * Loads a object either from file or from cache.
     *
     * @param string $schema_shortname Path to the schema JSON file.
     * @param string $component_class  Type of class to be returned on successful object creation.
     *
     * @return mixed Either an object or FALSE on error.
     */
    public function load($schema_shortname, $component_class = 'PatternBuilder\Property\Component\Component')
    {
        $cid = $this->getCid($schema_shortname);
        if ($this->use_cache && $cache = $this->getCache($cid)) {
            return clone $cache;
        } else {
            if (empty($this->schemaFiles)) {
                $this->schemaFiles = $this->getSchemaFiles();
            }

            if (!isset($this->schemaFiles[$schema_shortname])) {
                $message = sprintf('JSON shortname of %s was not found.', $schema_shortname);
                $this->logger->error($message);

                return false;
            }

            // Loading is limited to this branch of the IF to limit disk IO
            if ($json = $this->loadSchema($this->schemaFiles[$schema_shortname])) {
                $schema_path = $this->schemaFiles[$schema_shortname];
                $component = new $component_class($json, $this->configuration, $schema_shortname, $schema_path);
                $this->setCache($cid, $component);

                return $component;
            } else {
                return false;
            }
        }
    }

    /**
     * Returns the object from local or extended cache.
     *
     * @param string $cid Cache ID.
     *
     * @return mixed Returns the object or FALSE if not found in cache.
     */
    protected function getCache($cid)
    {
        if (isset($this->localCache[$cid])) {
            $cache = $this->localCache[$cid];
        } else {
            $cache = $this->loadCache($cid);
            if ($cache !== false) {
                $this->localCache[$cid] = $cache;
            }
        }

        return $cache;
    }

    /**
     * Sets the local and extended cache.
     *
     * @param string $cid           Path to JSON file.
     * @param object $component_obj Object to be stored in cache.
     */
    protected function setCache($cid, $component_obj)
    {
        $this->localCache[$cid] = $component_obj;
        $this->saveCache($cid, $component_obj);
    }

    /**
     * Load the JSON file from disk.
     *
     * @param string $schema_path Path to JSON file.
     *
     * @return mixed A PHP object from the JSON or FALSE if file not found.
     */
    protected function loadSchema($schema_path)
    {
        $json = false;
        if (file_exists($schema_path)) {
            $contents = file_get_contents($schema_path);
            $json = json_decode($contents);
            if ($json == false) {
                $message = sprintf('Error decoding %s.', $schema_path);
                $this->logger->error($message);
            }
        } else {
            $message = sprintf('Could not load file %s.', $schema_path);
            $this->logger->error($message);
        }

        return $json;
    }

    /**
     * Creates a unique name for the cached object.
     *
     * @param string $short_name Name to base the hash off of.
     *
     * @return string A hash of the short name.
     */
    protected function getCid($short_name)
    {
        return 'patternbuilder:'.md5($short_name);
    }

    /**
     * Clear the cached objects for the given cid.
     *
     * @param string $cid Cache ID.
     */
    public function clearCache($cid)
    {
        unset($this->localCache[$cid]);
    }

    /**
     * Clear the cached objects for the give schema short name.
     *
     * @param string $short_name
     *                           The schema short machine name.
     */
    public function clearCacheByName($short_name)
    {
        $cid = $this->getCid($short_name);
        unset($this->schemaFiles[$short_name]);
        $this->clearCache($cid);
    }

    /**
     * Clear all the cached schema objects.
     */
    public function clearAllCache()
    {
        $this->localCache = array();
        $this->schemaFiles = array();
    }

    /**
     * Pre-load and create all objects to be cached.
     *
     * @param string $render_class Type of class to be returned on successful object creation.
     */
    public function warmCache($render_class = 'PatternBuilder\Property\Component\Component')
    {
        $this->schemaFiles = $this->getSchemaFiles();
        foreach ($this->schemaFiles as $key => $schema_file) {
            if ($json = $this->loadSchema($schema_file)) {
                $cid = $this->getCid($key);
                $component = new $render_class($json);
                $this->setCache($cid, $component);
            }
        }
    }
}
