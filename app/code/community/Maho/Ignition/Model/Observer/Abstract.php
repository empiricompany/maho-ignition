<?php

declare(strict_types=1);

use Spatie\Ignition\Config\IgnitionConfig;
use Spatie\Ignition\Ignition;
use Spatie\Ignition\Solutions\OpenAi\OpenAiSolutionProvider;
use Spatie\FlareClient\Flare;
use Symfony\Component\HttpFoundation\AcceptHeader;

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
     * Developer mode + HTML request: Ignition renders the full error page and
     * owns the response. Spatie suppresses its own send because the instance
     * runs with inProductionEnvironment(false), so the report is forwarded
     * manually using the report Ignition already built. The caller must
     * terminate the response, otherwise Mage::printException() appends its own
     * <pre> dump after the rendered document.
     *
     * Developer mode + non-HTML request (API/JSON): Ignition never renders; the
     * exception is only reported to Flare and Maho owns the response.
     *
     * Production: Ignition is not involved at all. The standalone FlareClient
     * (no environment guard) reports the exception directly.
     *
     * @return bool True when Ignition rendered the error page and the caller
     *              must terminate the response.
     */
    protected function handleException(Throwable $exception): bool
    {
        if ($this->shouldRenderIgnition()) {
            $this->prepareIgnitionResponse();

            $ignition = $this->getIgnitionInstance();
            $report = $ignition->handleException($exception);

            if ($this->getFlareHelper()->canReport()) {
                $ignition->getFlare()->report($exception, report: $report);
            }

            return true;
        }

        if ($this->getFlareHelper()->canReport()) {
            $this->getFlareHelper()->getClient()->report($exception);
        }

        return false;
    }

    /**
     * Whether Ignition should render the error page for this request: only in
     * developer mode, when Ignition is enabled, and for an HTML response.
     * API and JSON requests are never given the Ignition HTML document.
     */
    protected function shouldRenderIgnition(): bool
    {
        return $this->getHelper()->shouldPrintIgnition() && $this->isHtmlRequest();
    }

    /**
     * Whether the current request expects an HTML document, using the framework
     * request API and content negotiation. CLI and the API area never qualify.
     */
    protected function isHtmlRequest(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        try {
            $request = Mage::app()->getRequest();
        } catch (\Throwable) {
            return false;
        }

        if (!$request) {
            return false;
        }

        // The API area answers with JSON, never with the Ignition HTML page
        if (str_starts_with((string) $request->getModuleName(), 'api')) {
            return false;
        }

        // No Accept header means "accept anything", including HTML
        $accept = (string) $request->getHeader('Accept');
        if ($accept === '') {
            return true;
        }

        // A request that negotiates something other than HTML has no use for
        // the rendered page: it is reported to Flare only
        $acceptHeader = AcceptHeader::fromString(strtolower($accept));

        return $acceptHeader->has('text/html') || $acceptHeader->has('*/*');
    }

    /**
     * Answer with a 500 and emit the headers before Ignition starts writing its
     * document: once the page is being written the status can no longer change,
     * and the error page would be served with the default 200.
     */
    protected function prepareIgnitionResponse(): void
    {
        try {
            $response = Mage::app()->getResponse();
            $response->setHttpResponseCode(500);
            $response->sendHeaders();
        } catch (\Throwable) {
            // Response not available: Ignition still renders, only the status is lost
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
