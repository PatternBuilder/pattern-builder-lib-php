<?php
/**
 * This file is part of the Pattern Builder library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PatternBuilder\Factory;

use PatternBuilder\Configuration\Configuration;
use PatternBuilder\Property\Component\Component;

/**
 * Factory class for instantiation of Component and Property objects.
 */
class ComponentFactory
{
    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    protected $twig;
    protected $configuration;

    /**
     * Constructor for the ComponentFactory.
     *
     * @param Configuration $configuration The configuration object.
     */
    public function __construct(Configuration $configuration)
    {
        $this->setLogger($configuration->getLogger());
        $this->configuration = $configuration;
    }

    public function create($properties, $schema_path)
    {
        if (isset($properties->{'$ref'})) {
            $resolver = $this->configuration->createResolver();
            $resolver->resolve($properties, $schema_path);
        }

        if ($properties->type == 'array') {
            $Class = 'PatternBuilder\Property\Component\CompositeComponent';
        } elseif ($properties->type == 'object') {
            $Class = 'PatternBuilder\Property\Component\Component';
        } else {
            $Class = 'PatternBuilder\Property\LeafProperty';
        }

        return new $Class($properties, $this->configuration);
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
