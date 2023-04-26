<?php
namespace axenox\Proxy;

use exface\Core\CommonLogic\Model\App;
use exface\Core\Facades\AbstractHttpFacade\HttpFacadeInstaller;
use exface\Core\Factories\FacadeFactory;
use axenox\Proxy\Facades\ProxyFacade;

class ProxyApp extends App
{
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Model\App::getInstaller()
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        $installer = parent::getInstaller($injected_installer);
        
        // Proxy facade
        $tplInstaller = new HttpFacadeInstaller($this->getSelector());
        $tplInstaller->setFacade(FacadeFactory::createFromString(ProxyFacade::class, $this->getWorkbench()));
        $installer->addInstaller($tplInstaller);
        
        return $installer;
    }
}