<?php

// SPDX-FileCopyrightText: 2026 Research Industrial Systems Engineering (RISE) Forschungs-, Entwicklungs- und Großprojektberatung GmbH
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Selenium;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Module\Icingadb\Common\Macros;
use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;
use Icinga\Module\Selenium\Common\IcingaDbDatabase;
use Icinga\Module\Selenium\Model\Testsuite;
use ipl\Sql\Connection;
use ipl\Stdlib\Filter;

class SuiteHelperIcingaDb extends SuiteHelper
{
    use Macros;

    protected $db;
    protected $suite;
    protected $data;
    protected $driver;
    protected $imagepath;
    protected $autoClose;
    protected $withImages;
    protected $minimal;


    public function __construct(Connection $db, Testsuite $suite,$autoClose=true,$withImages=true,$minimal=false,$removeImagesOnSuccess = false,$reference_object=null, $override_check_source = null)
    {
        parent::__construct( $db, $suite,$autoClose, $withImages, $minimal,$removeImagesOnSuccess, $reference_object, $override_check_source);

        $this->db = $db;
        $this->suite= $suite;
        $filePath = $suite->override_vars_file ??"";
        $fileWasApplied = false;
        $this->data = $suite->data;

        if($suite->generic){
            if($reference_object == null){
                $reference_object = $suite->reference_object;
            }
            if(strpos($reference_object,"!") === false){
                $reference_object = $this->getHost($reference_object);
            }else{
                $tmp = explode("!",$reference_object);
                $servicename = array_pop($tmp);
                $hostname = array_pop($tmp);
                $reference_object = $this->getService($hostname,$servicename);

            }
            if($reference_object == null){
                Logger::error("generic selenium testsuite can not be rendered without a valid host or service!");
                throw new \Exception("Host or Service not found!");
            }

            $filePath = $this->expandMacros($filePath,$reference_object);

            $this->data = $this->applyOverrideFile($this->data,$filePath);
            $fileWasApplied = true;
            $count =0;
            while($count < 10){
                $count++;
                $this->data = $this->expandMacros($this->data,$reference_object);
            }

        }
        if($fileWasApplied == false){
            $this->data = $this->applyOverrideFile($this->data,$filePath);

        }

        $this->data= json_decode($this->data,true);

    }

    public function getService($hostname,$servicename){
        return Service::on(IcingaDbDatabase::get())->with('host')
            ->filter(Filter::equal('service.name', $servicename))
            ->filter(Filter::equal('host.name', $hostname))
            ->first();
    }
    public function getHost($hostname){
        return Host::on(IcingaDbDatabase::get())
            ->filter(Filter::equal('name', $hostname))
            ->first();
    }
}
