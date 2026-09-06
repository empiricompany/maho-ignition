<?php

declare(strict_types=1);

class Maho_Ignition_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    public const IGNITION_CONFIG_PATH = '_ignition/update-config';

    #[Maho\Config\Observer('controller_front_init_routers')]
    /**
     * Initialize Controller Router
     */
    public function initControllerRouters(\Maho\Event\Observer $observer): void
    {
        $front = $observer->getEvent()['front'];
        $front->addRouter('_ignition', $this);
    }

    /**
     * Match the request in the form "_ignition/update-config", skip store code prefix
     *
     * @inheritDoc
     * @throws Mage_Core_Exception
     */
    #[\Override]
    public function match(Mage_Core_Controller_Request_Http $request): bool
    {
        /** @var Maho_Ignition_Helper_Data $_helper */
        $_helper = Mage::helper('maho_ignition');
        if (!$_helper->shouldPrintIgnition()) {
            return false;
        }

        $requestPathInfo = trim($request->getPathInfo(), '/');
        if ($requestPathInfo == self::IGNITION_CONFIG_PATH && $request->isPost()) {
            $module = 'maho_ignition';
            $controller = 'config';
            $action = 'update';
            $realModule = 'Maho_Ignition';

            $request->setModuleName($module);
            $request->setControllerName($controller);
            $request->setActionName($action);
            $request->setControllerModule($realModule);

            // set params from JSON body
            $rawBody = $request->getRawBody();
            if ($rawBody === false) {
                return false;
            }

            $jsonData = json_decode($rawBody, true);
            if (!is_array($jsonData)) {
                return false;
            }

            $request->setParams($jsonData);

            $dispatcher = new \Maho\Routing\ControllerDispatcher();
            return $dispatcher->dispatchForward($request, $this->getFront()->getResponse());
        }

        return false;
    }
}
