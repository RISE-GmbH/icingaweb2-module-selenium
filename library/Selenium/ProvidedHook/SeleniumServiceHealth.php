<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Selenium\ProvidedHook;

use Icinga\Application\Hook\HealthHook;
use Icinga\Authentication\Auth;
use ipl\Web\Url;


class SeleniumServiceHealth extends HealthHook
{
    /** @var object */
    public function getName()
    {
        return "selenium-service";
    }
    /** @var object */
    public function getObject()
    {

        $service = (object) [
            "name"=>$this->getName(),
            "allowlist"=>"1",
            "allowrestart"=>"1",
            "statuscmd" => "sudo /usr/bin/systemctl status icinga-selenium.service",
            "restartcmd" => "sudo /usr/bin/systemctl restart icinga-selenium.service"
        ];
        return $service;
    }

    public function getUrl()
    {

        if (!Auth::getInstance()->hasPermission("selenium/service/restart")) {
            return null;
        }


        if ($this->getObject() !== false) {
            return Url::fromPath('selenium/service/restart', ['name' => $this->getName()]);
        } else {
            return null;

        }

    }

    public function checkHealth()
    {

        $service = $this->getObject();
        $output = null;
        exec($service->statuscmd." 2>&1", $output);

        $active = preg_grep("/Active: active.*$/", $output);
        $inactive = preg_grep("/Active: .*$/", $output);
        $pid = preg_grep("/Main PID: (\d+?) /", $output);

        if ($active != false) {
            $text = array_pop($active);
            $pid = array_pop($pid);
            $state = 0;
            $message = $this->getName() . " is running:";

            $this->setState($state);
            $this->setMessage($message . " " . $text . ", " . $pid);

        } else {

            $state = 2;
            $message = "Service " . $this->getName() . " is not running:";
            if ($inactive != false) {
                $text = array_pop($inactive);
            } else {
                $text = implode(" ", $output);
            }

            $this->setState($state);
            $this->setMessage($message . " " . $text);
        }

    }


}

