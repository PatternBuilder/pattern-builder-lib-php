<?php

namespace PatternBuilder\Test;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Provides a mocked Configuration object.
     */
    public function getConfig()
    {
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->getMock();

        $twig = $this->getMock('Twig_Environment');

        $resolver = $this->getMockBuilder('JsonSchema\RefResolver')
            ->getMock();

        $resolver->expects($this->any())
            ->method('resolve')
            ->will($this->returnValue(null));

        $configuration = $this->getMockBuilder('PatternBuilder\Configuration\Configuration')
            ->setConstructorArgs(array($logger, $twig, $resolver))
            ->getMock();

        // We need to mock the configurations getLogger() method to return
        // a valid logger mock, since this is called by every component.
        $configuration->expects($this->any())
            ->method('getLogger')
            ->will($this->returnValue($logger));

        return $configuration;
    }

    /**
     * Instantiate a component object.
     *
     * @param string $schema_name A schema name.
     */
    public function getComponent($schema_name)
    {
        $configuration = $this->getConfig();
        $schema_text = $this->getJson($schema_name.'.json');
        $schema = json_decode($schema_text);

        return new \PatternBuilder\Property\Component\Component($schema, $configuration, $schema_name);
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
}
