<?php

namespace Icinga\Module\Selenium\ProvidedHook\Monitoring;

use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Hook\ServiceActionsHook;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Url;

class ServiceActions extends ServiceActionsHook
{
    public function getActionsForService(Service $service)
    {
        $nav = new Navigation();
        if($service->check_command != "check_by_selenium"){
            return $nav;
        }

        preg_match("/id=(\d+)/",$service->perfdata,$matches);
        if(count($matches)==2){
            $nav->addItem(new NavigationItem(t('Go to Testrun'), array(
                'url' => Url::fromPath('selenium/testrun/view', array('id' => $matches[1])),
                'target' => '_next',
                'icon' => 'beaker',
            )));
        }


        return $nav;
    }
}
