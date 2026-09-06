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
        if ($flareHelper->isFlareEnabled() && !empty($flareHelper->getFlareApiKey())) {
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
     * Handle an exception while keeping Flare reporting independent from the
     * developer-only Ignition page.
     */
    protected function handleException(Throwable $exception): void
    {
        $ignition = $this->getIgnitionInstance();
        $report = $ignition->handleException($exception);

        if (Mage::getIsDeveloperMode() && $this->getFlareHelper()->isFlareEnabled()) {
            $ignition->getFlare()->report($exception, report: $report);
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
