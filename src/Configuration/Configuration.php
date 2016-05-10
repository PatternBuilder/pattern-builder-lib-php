<?php

namespace PatternBuilder\Configuration;

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriRetriever;
use Psr\Log\LoggerInterface;

class Configuration
{
    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    protected $twig;
    protected $resolver;
    protected $developer_mode;

    public function __construct(LoggerInterface $logger, \Twig_Environment $twig, RefResolver $resolver, $developer_mode = false)
    {
        $this->logger = $logger;
        $this->twig = $twig;
        $this->resolver = $resolver;
        $this->developer_mode = $developer_mode;
    }

    /**
     * PHP Clone interface.
     *
     * Clone objects to break the original object reference.
     */
    public function __clone()
    {
        $this->logger = clone $this->logger;
        $this->twig = clone $this->twig;
        $this->resolver = clone $this->resolver;
    }

    /**
     * Return this configuration objects logger.
     *
     * @return Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Return this configuration objects twig environment.
     *
     * @return Psr\Log\LoggerInterface $logger
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * Return this configuration objects resolver environment.
     *
     * @return RefResolver $resolver
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Create a resolver with the same classes as the initialized resolver.
     *
     * @return RefResolver An instance of the resolver class.
     */
    public function createResolver()
    {
        $resolver = $this->getResolver();
        if (isset($resolver)) {
            $resolver_class = get_class($resolver);

        // Create new retriever.
        $retriever = $resolver->getUriRetriever();
            if (isset($retriever)) {
                $retriever_class = get_class($retriever);
                $new_retriever = new $retriever_class();
            } else {
                $new_retriever = new UriRetriever();
            }

        // Store max depth before init since maxDepth is set on the parent.
        $max_depth = $resolver::$maxDepth;

        // Create new resolver.
        $new_resolver = new $resolver_class($new_retriever);

        // Sync public static properties.
        $new_resolver::$maxDepth = $max_depth;
        } else {
            $new_retriever = new UriRetriever();
            $new_resolver = new RefResolver($new_retriever);
        }

        return $new_resolver;
    }

    /**
     * Return this configuration objects twig environment.
     *
     * @return bool True is developer mode is enabled, false otherwise.
     */
    public function developerMode()
    {
        return $this->developer_mode;
    }
}
