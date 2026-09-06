<?php

declare(strict_types=1);

class Maho_Ignition_Helper_Flare extends Mage_Core_Helper_Abstract
{
    protected $_moduleName = 'Maho_Ignition';

    public const XML_PATH_FLARE_ENABLED = 'dev/maho_ignition/enable_flare';
    public const XML_PATH_FLARE_API_KEY = 'dev/maho_ignition/flare_api_key';
    public const XML_PATH_FLARE_ANONYMIZE_IP = 'dev/maho_ignition/flare_anonymize_ip';


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
}
