<?php

/* originally from Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2 */
/* generated by icingaweb2-module-scaffoldbuilder | GPLv2+ */

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


    public function __construct(Connection $db, Testsuite $suite,$autoClose=true,$withImages=true,$minimal=false,$removeImagesOnSuccess = false,$reference_object=null)
    {
        parent::__construct( $db, $suite,$autoClose, $withImages, $minimal,$removeImagesOnSuccess, $reference_object);
        $this->db = $db;
        $this->suite= $suite;

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
            $this->data = json_decode($this->expandMacros($suite->data,$reference_object), true);
        }else{
            $this->data= json_decode($suite->data,true);
        }
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