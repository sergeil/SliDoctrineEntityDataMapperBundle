<?php

namespace Sli\DoctrineEntityDataMapperBundle\Tests\Functional\Preferences;

use Modera\FoundationBundle\Testing\FunctionalTestCase;
use Sli\DoctrineEntityDataMapperBundle\Preferences\PreferencesProviderInterface;
use Sli\DoctrineEntityDataMapperBundle\Preferences\SemanticConfigPreferencesProvider;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class SemanticConfigPreferencesProviderTest extends FunctionalTestCase
{
    public function testGet()
    {
        /* @var SemanticConfigPreferencesProvider $provider */
        $provider = self::$container->get('sli_doctrine_entity_data_mapper.preferences.semantic_provider');

        $this->assertInstanceOf(SemanticConfigPreferencesProvider::clazz(), $provider);

        // Tests/Fixtures/App/app/config/config.yml
        $this->assertEquals('d*m*y', $provider->get(PreferencesProviderInterface::DATE_FORMAT));
        $this->assertEquals('d*m*y H:i', $provider->get(PreferencesProviderInterface::DATETIME_FORMAT));
        $this->assertEquals('m*Y', $provider->get(PreferencesProviderInterface::MONTH_FORMAT));
    }
} 