<?php
// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Selenium\Controllers;


use Icinga\Module\Selenium\ProvidedHook\SeleniumServiceHealth;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Url;

class ServiceController extends Controller
{

    public function restartAction()
    {

        $service = (new SeleniumServiceHealth())->getObject();

        $confirm = $this->params->get('confirm');
        if($service != false){
            if($confirm ==="YES"){
                $this->assertPermission('selenium/service/restart');
                exec($service->restartcmd." 2>&1", $output);
                sleep(2);
                $this->getResponse()->setReloadWindow(true);
            }
            $url = Url::fromPath("selenium/service/restart",["confirm"=>"YES"]);

            exec($service->statuscmd." 2>&1", $output);

            $item = new NavigationItem("Restart Service");
            $item->setUrl($url);
            $item->setDescription("Restart {$service->name} Service");
            $item->setIcon("plug");
            $this->view->navigation=[$item];
            $this->view->tabs = $this->getTabs();
            $this->view->status = implode("\n",$output);
        }else{
            throw new SecurityException('No permission for %s',"restarting this service");
        }


    }

}