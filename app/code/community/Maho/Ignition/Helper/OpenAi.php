<?php

declare(strict_types=1);

use Composer\InstalledVersions;

class Maho_Ignition_Helper_OpenAi extends Mage_Core_Helper_Abstract
{
    protected $_moduleName = 'Maho_Ignition';

    public const XML_PATH_OPENAI_ENABLED = 'dev/maho_ignition/enable_openai';
    public const XML_PATH_OPENAI_KEY = 'dev/maho_ignition/openai_api_key';

    /**
     * Check if OpenAI is enabled
     */
    public function isOpenAiEnabled(): bool
    {
        if (!InstalledVersions::isInstalled('openai-php/client')) {
            return false;
        }

        return Mage::getStoreConfigFlag(self::XML_PATH_OPENAI_ENABLED);
    }

    /**
     * Get OpenAI key
     */
    public function getOpenAiKey(): string
    {
        return (string) Mage::getStoreConfig(self::XML_PATH_OPENAI_KEY);
    }
}
