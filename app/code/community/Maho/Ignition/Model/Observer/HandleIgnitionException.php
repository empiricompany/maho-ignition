<?php

declare(strict_types=1);

class Maho_Ignition_Model_Observer_HandleIgnitionException extends Maho_Ignition_Model_Observer_Abstract
{
    #[Maho\Config\Observer('mage_run_exception')]
    #[Maho\Config\Observer('mage_run_installed_exception')]
    /**
     * Handle Exception with Ignition.
     */
    public function execute(\Maho\Event\Observer $observer): void
    {
        try {
            if (!$this->getHelper()->shouldPrintIgnition() && !$this->getFlareHelper()->canReport()) {
                return;
            }

            $exception = $observer->getEvent()['exception'];
            if (!$exception instanceof Throwable) {
                return;
            }

            // When Ignition renders the error page it owns the response: it has
            // already emitted a full HTML document, so the process must stop
            // here. Otherwise Mage::run() goes on to call printException(),
            // appending a raw <pre> dump after the rendered page.
            if ($this->handleException($exception)) {
                $this->terminateRenderedResponse();
            }
        } catch (Throwable $integrationException) {
            Mage::log('Ignition exception handling failed: ' . $integrationException::class, null, 'maho_ignition.log', true);
            throw $integrationException;
        }
    }

    /**
     * Emit the page Ignition already rendered and stop the process. Mirrors the
     * output-buffer flushing of Mage::printException() so nothing buffered is
     * lost. The 500 status and the headers are set by
     * Abstract::handleException() before Ignition writes its document.
     */
    protected function terminateRenderedResponse(): never
    {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        exit(1);
    }
}
