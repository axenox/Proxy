<?php
namespace axenox\Proxy\Common;

use exface\Core\CommonLogic\AppInstallers\MetaModelAdditionInstaller;
use exface\Core\Interfaces\Selectors\SelectorInterface;
use exface\Core\Interfaces\InstallerContainerInterface;

/**
 * Allows to add proxy routes to app packages
 *
 * Add this to the app containing proxy routes:
 *
 * ```
 * $installer = new ProxyRouteInstaller($this->getSelector(), $container);
 * $container->addInstaller($installer);
 *
 * ```
 *
 * @author Andrej Kabachnik
 *
 */
class ProxyRouteInstaller extends MetaModelAdditionInstaller
{
    /**
     *
     * @param SelectorInterface $selectorToInstall
     * @param InstallerContainerInterface $installerContainer
     */
    public function __construct(SelectorInterface $selectorToInstall, InstallerContainerInterface $installerContainer)
    {
        parent::__construct($selectorToInstall, $installerContainer);
        $modelFolder = 'Proxy';
        $this->addModelDataSheet($modelFolder, $this->createModelDataSheet('axenox.Proxy.proxy_route', 'APP', 'MODIFIED_ON'));
    }
}