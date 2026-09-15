<?php

declare(strict_types=1);

namespace Maho\Ignition\Tests;

use Maho\Config\Observer as ObserverAttribute;
use Maho_Ignition_Controller_Router;
use Maho_Ignition_Helper_Flare;
use Maho_Ignition_Model_Observer_HandleIgnitionException;
use Maho_Ignition_Model_Observer_HandleIgnitionRegister;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ModuleStructureTest extends TestCase
{
    public function testModuleClassesAreAutoloadable(): void
    {
        self::assertTrue(class_exists(Maho_Ignition_Helper_Flare::class));
        self::assertTrue(class_exists(Maho_Ignition_Model_Observer_HandleIgnitionRegister::class));
        self::assertTrue(class_exists(Maho_Ignition_Model_Observer_HandleIgnitionException::class));
        self::assertTrue(class_exists(Maho_Ignition_Controller_Router::class));
    }

    /**
     * Observers are declared with Maho\Config\Observer attributes, never XML.
     */
    public function testObserversAreDeclaredWithAttributes(): void
    {
        self::assertSame(
            ['controller_front_init_before'],
            self::observedEvents(Maho_Ignition_Model_Observer_HandleIgnitionRegister::class, 'execute'),
        );
        self::assertSame(
            ['mage_run_exception', 'mage_run_installed_exception'],
            self::observedEvents(Maho_Ignition_Model_Observer_HandleIgnitionException::class, 'execute'),
        );
        self::assertSame(
            ['controller_front_init_routers'],
            self::observedEvents(Maho_Ignition_Controller_Router::class, 'initControllerRouters'),
        );
    }

    /**
     * Flare must transmit real errors and warnings without flooding the project
     * with deprecations and notices; Maho still logs every level locally.
     */
    public function testFlareReportsRealErrorsButNotNoise(): void
    {
        $levels = Maho_Ignition_Helper_Flare::REPORTED_ERROR_LEVELS;

        self::assertNotSame(0, $levels & E_ERROR);
        self::assertNotSame(0, $levels & E_WARNING);
        self::assertNotSame(0, $levels & E_USER_ERROR);
        self::assertSame(0, $levels & E_NOTICE);
        self::assertSame(0, $levels & E_DEPRECATED);
        self::assertSame(0, $levels & E_USER_DEPRECATED);
    }

    public function testFatalErrorLevelsCoverUncatchableErrors(): void
    {
        self::assertSame(
            [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR],
            Maho_Ignition_Model_Observer_HandleIgnitionRegister::FATAL_ERROR_LEVELS,
        );
    }

    /**
     * @return list<string>
     */
    private static function observedEvents(string $class, string $method): array
    {
        $events = [];
        foreach ((new ReflectionMethod($class, $method))->getAttributes(ObserverAttribute::class) as $attribute) {
            $events[] = $attribute->newInstance()->event;
        }

        return $events;
    }
}
