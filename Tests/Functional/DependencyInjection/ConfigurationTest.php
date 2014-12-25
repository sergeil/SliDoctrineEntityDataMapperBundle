<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Functional\DependencyInjection;

use Sli\DoctrineEntityDataMapperBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConfigurationValues()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), array());

        $this->assertTrue(is_array($config));
        $this->assertArrayHasKey('preferences_provider_formats', $config);
        $this->assertTrue(is_array($config['preferences_provider_formats']));
        $formats = $config['preferences_provider_formats'];
        $this->assertArrayHasKey('date', $formats);
        $this->assertTrue('' != $formats['date']);
        $this->assertArrayHasKey('datetime', $formats);
        $this->assertTrue('' != $formats['datetime']);
        $this->assertArrayHasKey('month', $formats);
        $this->assertTrue('' != $formats['month']);

        $this->assertArrayHasKey('preferences_provider', $config);
        $this->assertTrue('' != $config['preferences_provider']);
    }
}