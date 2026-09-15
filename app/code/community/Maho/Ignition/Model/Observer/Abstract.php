<?php

declare(strict_types=1);

use Spatie\Ignition\Config\IgnitionConfig;
use Spatie\Ignition\Ignition;
use Spatie\Ignition\Solutions\OpenAi\OpenAiSolutionProvider;
use Spatie\FlareClient\Flare;

abstract class Maho_Ignition_Model_Observer_Abstract extends Mage_Core_Model_Observer
{
    /**
     * Get helper data.
     */
    protected function getHelper(): Maho_Ignition_Helper_Data
    {
        /** @var Maho_Ignition_Helper_Data $helper */
        $helper = Mage::helper('maho_ignition');
        return $helper;
    }

    /**
     * Get helper Flare.
     */
    protected function getFlareHelper(): Maho_Ignition_Helper_Flare
    {
        /** @var Maho_Ignition_Helper_Flare $helper */
        $helper = Mage::helper('maho_ignition/flare');
        return $helper;
    }

    /**
     * Get helper OpenAi.
     */
    protected function getOpenAiHelper(): Maho_Ignition_Helper_OpenAi
    {
        /** @var Maho_Ignition_Helper_OpenAi $helper */
        $helper = Mage::helper('maho_ignition/openAi');
        return $helper;
    }

    /**
     * Get the Ignition instance.
     */
    protected function getIgnitionInstance(): Ignition
    {
        $_ignition = Ignition::make()
            ->runningInProductionEnvironment(!Mage::getIsDeveloperMode())
            ->shouldDisplayException($this->getHelper()->shouldPrintIgnition() && Mage::getIsDeveloperMode())
            ->setConfig($this->getIgnitionConfig())
            ->applicationPath(Mage::getBaseDir());

        $openAiHelper = $this->getOpenAiHelper();
        if ($openAiHelper->isOpenAiEnabled() && !empty($openAiHelper->getOpenAiKey())) {
            $openAiKey = $openAiHelper->getOpenAiKey();
            $aiSolutionProvider = new OpenAiSolutionProvider($openAiKey);
            $aiSolutionProvider->applicationType(
                'Maho Commerce (official PHPDoc: https://phpdoc.mahocommerce.com/)',
            );

            $_ignition->addSolutionProviders([
                $aiSolutionProvider,
            ]);
        }

        $flareHelper = $this->getFlareHelper();
        if ($flareHelper->canReport()) {
            $_ignition->sendToFlare($flareHelper->getFlareApiKey());
            if ($flareHelper->shouldAnonymizeIp()) {
                $_ignition->configureFlare(function (Flare $flare) {
                    $flare->anonymizeIp();
                });
            }
        }

        return $_ignition;
    }

    /**
     * Handle an exception, keeping the developer-mode Ignition page and the
     * Flare reporting as two independent concerns.
     *
     * Developer mode: Ignition renders the page. Spatie suppresses its own send
     * because the instance runs with inProductionEnvironment(false), so the
     * report is forwarded manually using the report Ignition already built.
     *
     * Production: Ignition is not involved at all. The standalone FlareClient
     * (no environment guard) reports the exception directly.
     */
    protected function handleException(Throwable $exception): void
    {
        if (Mage::getIsDeveloperMode()) {
            $ignition = $this->getIgnitionInstance();
            $report = $ignition->handleException($exception);

            if ($this->getFlareHelper()->canReport()) {
                $ignition->getFlare()->report($exception, report: $report);
            }

            return;
        }

        if ($this->getFlareHelper()->canReport()) {
            $this->getFlareHelper()->getClient()->report($exception);
        }
    }

    /**
     * Get the Ignition config.
     */
    protected function getIgnitionConfig(): IgnitionConfig
    {
        return (new IgnitionConfig())
            ->merge($this->getSystemConfig());
    }

    /**
     * Get settings from system config.
     *
     * @return array<string, string>
     */
    protected function getSystemConfig(): array
    {
        $helper = $this->getHelper();
        return [
            'editor' => $helper->getEditor() ?? 'clipboard',
            'theme' => $helper->getTheme() ?? 'auto',
        ];
    }
}
