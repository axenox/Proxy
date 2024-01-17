<?php
namespace axenox\Proxy\Common;

use exface\Core\CommonLogic\AppInstallers\MetaModelAdditionInstaller;
use exface\Core\Interfaces\Selectors\SelectorInterface;
use exface\Core\Interfaces\InstallerContainerInterface;
use exface\Core\CommonLogic\AppInstallers\AbstractAppInstaller;

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
class ProxyRouteInstaller extends AbstractAppInstaller
{
    private $additionInstaller = null;
    
    /**
     *
     * @param SelectorInterface $selectorToInstall
     * @param InstallerContainerInterface $installerContainer
     */
    public function __construct(SelectorInterface $selectorToInstall, InstallerContainerInterface $installerContainer)
    {
        $this->additionInstaller = (new MetaModelAdditionInstaller($selectorToInstall, $installerContainer, 'Proxy'))
        ->addDataToReplace('axenox.Proxy.proxy_route', 'CREATED_ON', 'APP');
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\InstallerInterface::backup()
     */
    public function backup(string $absolute_path): \Iterator
    {
        return $this->additionInstaller->backup($absolute_path);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\InstallerInterface::uninstall()
     */
    public function uninstall(): \Iterator
    {
        return $this->additionInstaller->uninstall();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\InstallerInterface::install()
     */
    public function install(string $source_absolute_path): \Iterator
    {
        return $this->additionInstaller->install($source_absolute_path);
    }
}