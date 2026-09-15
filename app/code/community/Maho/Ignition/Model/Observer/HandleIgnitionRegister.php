<?php

declare(strict_types=1);

use Spatie\FlareClient\Flare;

class Maho_Ignition_Model_Observer_HandleIgnitionRegister extends Maho_Ignition_Model_Observer_Abstract
{
    /**
     * PHP error levels that terminate the script before any handler runs and
     * can therefore only be reported from a shutdown callback.
     */
    public const FATAL_ERROR_LEVELS = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    #[Maho\Config\Observer('controller_front_init_before')]
    /**
     * Register the error handlers required by the active mode.
     */
    public function execute(\Maho\Event\Observer $observer): void
    {
        try {
            // Developer mode: Ignition owns warnings and uncaught exceptions so
            // its error page can show them. Flare is reported separately by the
            // exception observer (see Abstract::handleException).
            if ($this->getHelper()->shouldPrintIgnition()) {
                $this->getIgnitionInstance()->register(error_reporting());
                return;
            }

            // Production: Ignition is never instantiated, so renderError() can
            // no longer turn warnings into fatal ErrorExceptions. FlareClient
            // reports to Flare and then delegates to the previous handler,
            // preserving Maho's logging behaviour.
            if (!$this->getFlareHelper()->canReport()) {
                return;
            }

            $flare = $this->getFlareHelper()->getClient();

            // Uncaught exceptions and engine errors are reported unfiltered;
            // Spatie's reportErrorLevels() is intentionally not used because it
            // keys on Error::getCode() (0 for engine errors) and would drop them.
            $flare->registerExceptionHandler();

            $this->registerErrorHandler($flare);
            $this->registerFatalErrorReporter($flare);
        } catch (Throwable $exception) {
            Mage::log('Ignition registration failed: ' . $exception::class, null, 'maho_ignition.log', true);
            throw $exception;
        }
    }

    /**
     * Report the error levels that matter (see the Flare helper mask) and then
     * delegate to the previous handler, preserving Maho's logging behaviour
     * instead of turning warnings into fatal errors.
     */
    protected function registerErrorHandler(Flare $flare): void
    {
        $previous = null;

        $previous = set_error_handler(
            static function (int $level, string $message, string $file = '', int $line = 0) use ($flare, &$previous): mixed {
                if ((Maho_Ignition_Helper_Flare::REPORTED_ERROR_LEVELS & $level) !== 0) {
                    $flare->report(new ErrorException($message, 0, $level, $file, $line));
                }

                return is_callable($previous) ? $previous($level, $message, $file, $line) : false;
            },
        );
    }

    /**
     * Report PHP fatal errors (OOM, execution timeout, parse/compile errors) to
     * Flare, which no error handler can catch.
     *
     * The FlareClient's Api registers its own "flush the queue" shutdown
     * callback when it is built, i.e. before this one, and shutdown callbacks
     * run in registration order. Reporting here therefore queues after that
     * flush already ran, so the queue is flushed again to guarantee delivery.
     */
    protected function registerFatalErrorReporter(Flare $flare): void
    {
        register_shutdown_function(static function () use ($flare): void {
            $lastError = error_get_last();

            if ($lastError === null || !in_array($lastError['type'], self::FATAL_ERROR_LEVELS, true)) {
                return;
            }

            $error = new ErrorException(
                $lastError['message'],
                0,
                $lastError['type'],
                $lastError['file'],
                $lastError['line'],
            );

            $flare->report($error);
            $flare->reset();
        });
    }
}
