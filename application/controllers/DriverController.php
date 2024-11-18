<?php
// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Selenium\Controllers;


use Icinga\Authentication\Auth;
use Icinga\Module\Selenium\BinaryHelper;

use Icinga\Module\Selenium\ProvidedHook\SeleniumDriverHealth;
use Icinga\Security\SecurityException;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Url;
use ipl\Web\Compat\CompatController;

class DriverController extends CompatController
{

    public function initAction()
    {
        $this->assertPermission('selenium/driver/init');

        $driver = new SeleniumDriverHealth();
        $driver->checkHealth();
        $confirm = $this->params->get('confirm');
        if(Auth::getInstance()->hasPermission('selenium/driver/init')){
            if($confirm ==="YES"){
                $b = new BinaryHelper();
                if($b->needsUpdate()[0] == true){
                    $b->update();
                }
                $this->getResponse()->setReloadWindow(true);
            }
            $url = Url::fromPath("selenium/driver/init",["confirm"=>"YES"]);

            $item = new NavigationItem("Init/Update Chrome Webdriver");
            $item->setUrl($url);
            $item->setDescription("Init/Update Chrome Webdriver");
            $item->setIcon("plug");
            $this->view->navigation=[$item];
            $this->view->tabs = $this->getTabs();
            $this->view->status = $driver->getMessage();
        }else{
            throw new SecurityException('No permission for %s',"initializeing chrome webdriver");
        }


    }

}