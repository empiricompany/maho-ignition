<?php

declare(strict_types=1);

namespace Maho\Ignition\Tests;

use Maho_Ignition_Helper_Data;
use Maho_Ignition_Model_Observer_HandleIgnitionException;
use PHPUnit\Framework\TestCase;

/**
 * The rendering decision is exercised with stubbed helpers. The tests run on the
 * command line, so isHtmlRequest() always reports false there: Ignition must
 * never write its HTML document to stdout.
 */
final class HandleIgnitionExceptionTest extends TestCase
{
    public function testIgnitionNeverRendersOnTheCommandLine(): void
    {
        $observer = $this->observer(shouldPrintIgnition: true);

        self::assertFalse($observer->exposeIsHtmlRequest());
        self::assertFalse($observer->exposeShouldRenderIgnition());
    }

    public function testIgnitionDoesNotRenderWhenItIsDisabled(): void
    {
        $observer = $this->observer(shouldPrintIgnition: false);

        self::assertFalse($observer->exposeShouldRenderIgnition());
    }

    private function observer(bool $shouldPrintIgnition): object
    {
        $dataHelper = new class ($shouldPrintIgnition) extends Maho_Ignition_Helper_Data {
            public function __construct(private bool $shouldPrint) {}

            public function shouldPrintIgnition(): bool
            {
                return $this->shouldPrint;
            }
        };

        return new class ($dataHelper) extends Maho_Ignition_Model_Observer_HandleIgnitionException {
            public function __construct(private Maho_Ignition_Helper_Data $dataHelper) {}

            protected function getHelper(): Maho_Ignition_Helper_Data
            {
                return $this->dataHelper;
            }

            public function exposeIsHtmlRequest(): bool
            {
                return $this->isHtmlRequest();
            }

            public function exposeShouldRenderIgnition(): bool
            {
                return $this->shouldRenderIgnition();
            }
        };
    }
}
