<?php

declare(strict_types=1);

use Spatie\FlareClient\Flare;

class Maho_Ignition_Helper_Flare extends Mage_Core_Helper_Abstract
{
    protected $_moduleName = 'Maho_Ignition';

    public const XML_PATH_FLARE_ENABLED = 'dev/maho_ignition/enable_flare';
    public const XML_PATH_FLARE_API_KEY = 'dev/maho_ignition/flare_api_key';
    public const XML_PATH_FLARE_ANONYMIZE_IP = 'dev/maho_ignition/flare_anonymize_ip';

    /**
     * Error levels worth transmitting to Flare. Maho keeps logging every level
     * through its own handler; this mask only filters what leaves the server,
     * so deprecations and notices cannot flood the project.
     *
     * It is applied by the module's own error handler, not by Spatie's
     * reportErrorLevels(): that option keys on an Error's `getCode()`, which is
     * 0 for engine errors (TypeError, undefined function, ...), so it would
     * silently drop every uncaught Error.
     */
    public const REPORTED_ERROR_LEVELS = E_ERROR | E_WARNING | E_PARSE | E_CORE_ERROR
        | E_COMPILE_ERROR | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR;

    /**
     * Standalone FlareClient, built lazily and reused for the whole request.
     */
    private ?Flare $client = null;

    /**
     * Check if Flare is enabled
     */
    public function isFlareEnabled(): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_FLARE_ENABLED);
    }

    /**
     * Check if Flare should anonymize IP
     */
    public function shouldAnonymizeIp(): bool
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_FLARE_ANONYMIZE_IP);
    }

    /**
     * Get Flare API key
     */
    public function getFlareApiKey(): string
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_FLARE_API_KEY);
    }

    /**
     * Flare can actually report only when it is enabled and carries a usable
     * API key. Registering handlers (or reporting) without a key would only
     * produce silent, failed HTTP calls.
     */
    public function canReport(): bool
    {
        return $this->isFlareEnabled() && $this->getFlareApiKey() !== '';
    }

    /**
     * Build the pure FlareClient used outside the developer-mode Ignition page.
     *
     * Unlike Spatie\Ignition\Ignition, this client has no environment guard, so
     * it remains active in production where Ignition is intentionally not
     * rendered. It is memoized: a single client means a single Api instance and
     * therefore a single shutdown callback.
     */
    public function getClient(): Flare
    {
        if ($this->client === null) {
            $flare = Flare::make($this->getFlareApiKey());
            $flare->applicationPath(Mage::getBaseDir());

            if ($this->shouldAnonymizeIp()) {
                $flare->anonymizeIp();
            }

            $this->client = $flare;
        }

        return $this->client;
    }
}
