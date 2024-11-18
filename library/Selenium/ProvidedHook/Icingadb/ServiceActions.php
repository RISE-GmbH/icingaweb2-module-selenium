<?php

namespace Icinga\Module\Selenium\ProvidedHook\Icingadb;

use Icinga\Module\Icingadb\Hook\ServiceActionsHook;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Web\Url;
use ipl\Web\Widget\Link;

class ServiceActions extends ServiceActionsHook
{

    public function getActionsForObject(Service $service): array
    {
        if($service->checkcommand_name != "check_by_selenium"){
            return [];
        }

        preg_match("/id=(\d+)/",$service->state->performance_data,$matches);
        if(count($matches)==2){
            return [
                new Link(
                    mt('selenium', 'Go to Testrun'),
                    Url::fromPath('selenium/testrun/view', ['id' => $matches[1]])->getAbsoluteUrl(),
                    ["class"=>"icon-beaker"]
                ),



            ];
        }else{
            return [];
        }
    }
}
