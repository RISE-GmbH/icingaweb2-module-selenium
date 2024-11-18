<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Selenium\ProvidedHook;

use Icinga\Application\Hook\HealthHook;
use Icinga\Authentication\Auth;
use Icinga\Module\Selenium\BinaryHelper;
use ipl\Web\Url;


class SeleniumDriverHealth extends HealthHook
{
    /** @var object */
    public function getName()
    {
        return "selenium-driver";
    }
    /** @var object */
    public function getObject()
    {

    }

    public function getUrl()
    {

        if (!Auth::getInstance()->hasPermission("selenium/driver/init")) {
            return null;
        }

        return Url::fromPath('selenium/driver/init');


    }

    public function checkHealth()
    {

        $this->setState(0);
        $a = new BinaryHelper();
        list($needsUpdate,$chromeVersion,$driverVersion) = $a->needsUpdate();
        if($needsUpdate){
            if($driverVersion == "0"){
                $this->setState(2);
                $this->setMessage($this->getMessage() . "\n" . sprintf("ChromeDriver not installed",$chromeVersion,$driverVersion));

            }else{
                $this->setState(1);
                $this->setMessage($this->getMessage() . "\n" . sprintf("ChromeDriver not the right version %s != %s",$chromeVersion,$driverVersion));
            }
        }else{
            $this->setMessage($this->getMessage() . "\n" . sprintf("ChromeDriver version Ok %s == %s",$chromeVersion,$driverVersion));
        }
    }


}

