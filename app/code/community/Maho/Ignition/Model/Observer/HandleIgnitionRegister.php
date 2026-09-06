<?php

declare(strict_types=1);

class Maho_Ignition_Model_Observer_HandleIgnitionRegister extends Maho_Ignition_Model_Observer_Abstract
{
    #[Maho\Config\Observer('controller_front_init_before')]
    /**
     * Register Ignition error handler.
     */
    public function execute(\Maho\Event\Observer $observer): void
    {
        try {
            if (!$this->getHelper()->shouldPrintIgnition() && !$this->getFlareHelper()->isFlareEnabled()) {
                return;
            }

            Mage::app()->setErrorHandler(null);
            $this->getIgnitionInstance()->register(error_reporting());
        } catch (Throwable $exception) {
            Mage::log('Ignition registration failed: ' . $exception::class, null, 'maho_ignition.log', true);
            throw $exception;
        }
    }
}
