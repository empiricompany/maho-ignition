<?php

declare(strict_types=1);

namespace Maho\Ignition\Tests;

use Closure;
use Maho\Event\Observer as EventObserver;
use Maho_Ignition_Helper_Data;
use Maho_Ignition_Helper_Flare;
use Maho_Ignition_Model_Observer_HandleIgnitionRegister;
use PHPUnit\Framework\TestCase;
use Spatie\FlareClient\Flare;
use Spatie\Ignition\Ignition;

/**
 * The registration observer is exercised with stubbed helpers, so the two
 * modes can be asserted without bootstrapping Maho or touching a database.
 */
final class HandleIgnitionRegisterTest extends TestCase
{
    private mixed $originalExceptionHandler = null;

    protected function setUp(): void
    {
        $this->originalExceptionHandler = set_exception_handler(null);
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        set_exception_handler($this->originalExceptionHandler);
    }

    public function testProductionRegistersFlareErrorHandler(): void
    {
        $flare = Flare::make('test-key');

        $this->observer(shouldPrintIgnition: false, flareClient: $flare)
            ->execute(new EventObserver());

        $handler = self::takeRegisteredErrorHandler();
        self::assertInstanceOf(Closure::class, $handler);
    }

    public function testDeveloperModeRegistersTheIgnitionRenderer(): void
    {
        $ignition = Ignition::make();

        $this->observer(shouldPrintIgnition: true, ignition: $ignition)
            ->execute(new EventObserver());

        $handler = self::takeRegisteredErrorHandler();
        self::assertIsArray($handler);
        self::assertSame($ignition, $handler[0]);
        self::assertSame('renderError', $handler[1]);
    }

    public function testNothingIsRegisteredWhenNeitherModeApplies(): void
    {
        $before = self::captureErrorHandler();

        $this->observer(shouldPrintIgnition: false)
            ->execute(new EventObserver());

        self::assertSame($before, self::captureErrorHandler());
    }

    private function observer(
        bool $shouldPrintIgnition,
        ?Flare $flareClient = null,
        ?Ignition $ignition = null,
    ): Maho_Ignition_Model_Observer_HandleIgnitionRegister {
        $dataHelper = new class ($shouldPrintIgnition) extends Maho_Ignition_Helper_Data {
            public function __construct(private bool $shouldPrint) {}

            public function shouldPrintIgnition(): bool
            {
                return $this->shouldPrint;
            }
        };

        $flareHelper = new class ($flareClient) extends Maho_Ignition_Helper_Flare {
            public function __construct(private ?Flare $client) {}

            public function canReport(): bool
            {
                return $this->client !== null;
            }

            public function getClient(): Flare
            {
                return $this->client ?? Flare::make('unused');
            }
        };

        return new class ($dataHelper, $flareHelper, $ignition ?? Ignition::make()) extends Maho_Ignition_Model_Observer_HandleIgnitionRegister {
            public function __construct(
                private Maho_Ignition_Helper_Data $dataHelper,
                private Maho_Ignition_Helper_Flare $flareHelper,
                private Ignition $ignition,
            ) {}

            protected function getHelper(): Maho_Ignition_Helper_Data
            {
                return $this->dataHelper;
            }

            protected function getFlareHelper(): Maho_Ignition_Helper_Flare
            {
                return $this->flareHelper;
            }

            protected function getIgnitionInstance(): Ignition
            {
                return $this->ignition;
            }
        };
    }

    /**
     * Read the handler left installed by the observer and remove it, so the
     * baseline handler is restored and PHPUnit does not flag a leftover.
     */
    private static function takeRegisteredErrorHandler(): mixed
    {
        $handler = set_error_handler(static fn(): bool => false);
        restore_error_handler();
        restore_error_handler();

        return $handler;
    }

    private static function captureErrorHandler(): mixed
    {
        $handler = set_error_handler(static fn(): bool => false);
        restore_error_handler();

        return $handler;
    }
}
