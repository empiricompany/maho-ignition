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
            if (!$this->getHelper()->shouldPrintIgnition() && !$this->getFlareHelper()->isFlareEnabled()) {
                return;
            }

            $exception = $observer->getEvent()['exception'];
            if ($exception instanceof Throwable) {
                $this->handleException($exception);
            }
        } catch (Throwable $integrationException) {
            Mage::log('Ignition exception handling failed: ' . $integrationException::class, null, 'maho_ignition.log', true);
            throw $integrationException;
        }

        // Ignition owns the response when it renders an error page. Do not
        // terminate the PHP process here: Maho's exception dispatcher handles
        // the lifecycle and JSON requests safely.
    }
}
