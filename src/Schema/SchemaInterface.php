<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PatternBuilder\Schema;

use PatternBuilder\Property\Component\Component;

/**
 * Interface to load a schema object.
 */
interface SchemaInterface
{
    /**
     * Returns a list of json files available.
     *
     * @return array Associative array of shortname => path to file
     */
    public function getSchemaFiles();

    /**
     * Returns the object from system cache.
     *
     * @param string $cid Cache ID.
     *
     * @return mixed Returns the object or FALSE if not found in cache.
     */
    public function loadCache($cid);

    /**
     * Returns the object from system cache.
     *
     * @param string    $cid           Cache ID.
     * @param Component $component_obj Component Object
     */
    public function saveCache($cid, Component $component_obj);

    /**
     * Clear the cached objects for the given cid.
     *
     * @param string $cid Cache ID.
     */
    public function clearCache($cid);

    /**
     * Clear all the cached objects.
     */
    public function clearAllCache();
}
