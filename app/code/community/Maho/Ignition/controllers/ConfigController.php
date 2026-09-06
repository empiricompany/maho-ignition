<?php

declare(strict_types=1);

class Maho_Ignition_ConfigController extends Mage_Core_Controller_Front_Action
{
    public function updateAction(): void
    {
        /** @var array<string> $params */
        $params = $this->getRequest()->getParams();
        $result = $this->updateConfig($params);
        $response = $this->getResponse();
        if (!$result) {
            $response->setHttpResponseCode(400);
        }

        $response->setHeader('Content-type', 'application/json');
        $encodedResult = json_encode($result);
        if ($encodedResult === false) {
            $encodedResult = $result ? 'true' : 'false';
        }
        $response->setBody($encodedResult);
    }

    /**
     * @param array<string> $config
     */
    private function updateConfig(array $config): bool
    {
        /** @var Maho_Ignition_Helper_Data $_helper */
        $_helper = Mage::helper('maho_ignition');
        if (isset($config['theme'])) {
            $_helper->setTheme($config['theme']);
        }
        if (isset($config['editor'])) {
            $_helper->setEditor($config['editor']);
        }
        return true;
    }
}
