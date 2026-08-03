<?php

// SPDX-FileCopyrightText: 2026 Research Industrial Systems Engineering (RISE) Forschungs-, Entwicklungs- und Großprojektberatung GmbH
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Selenium;


use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Data\Filterable;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\QueryException;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Selenium\Model\Testsuite;
use ipl\Sql\Connection;
use Icinga\Data\Filter\Filter;


class SuiteHelperIdo extends SuiteHelper
{

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

            $filePath = IdoMacro::resolveMacros($filePath,$reference_object);

            $this->data = $this->applyOverrideFile($this->data,$filePath);
            $fileWasApplied = true;
            $count =0;
            while($count < 10){
                $count++;
                $this->data = IdoMacro::resolveMacros($this->data, $reference_object);
            }

        }
        if($fileWasApplied === false){
            $this->data = $this->applyOverrideFile($this->data,$filePath);

        }

        $this->data= json_decode($this->data,true);

    }

    public function getHost($hostname){
        $host = new Host($this->getBackend(), $hostname);
        $this->applyRestriction('monitoring/filter/objects', $host);
        if ($host->fetch() === false) {
            return null;
        }else{
            return $host;
        }
    }

    public function getService($hostname,$servicename){
        $service = new Service(
            $this->getBackend(),
            $hostname,
            $servicename
        );
        $this->applyRestriction('monitoring/filter/objects', $service);
        if ($service->fetch() === false) {
            return null;
        }else{
            return $service;
        }
    }
    protected function getBackend()
    {
        MonitoringBackend::clearInstances();

        return MonitoringBackend::instance();
    }
    /**
     * Apply a restriction of the authenticated on the given filterable
     *
     * @param   string      $name       Name of the restriction
     * @param   Filterable  $filterable Filterable to restrict
     *
     * @return  Filterable  The filterable having the restriction applied
     */
    protected function applyRestriction($name, Filterable $filterable)
    {
        $filterable->applyFilter($this->getRestriction($name));
        return $filterable;
    }

    /**
     * Get a restriction of the authenticated
     *
     * @param   string $name        Name of the restriction
     *
     * @return  Filter              Filter object
     * @throws  ConfigurationError  If the restriction contains invalid filter columns
     */
    protected function getRestriction($name)
    {
        $restriction = Filter::matchAny();
        $restriction->setAllowedFilterColumns(array(
            'host_name',
            'hostgroup_name',
            'instance_name',
            'service_description',
            'servicegroup_name',
            function ($c) {
                return preg_match('/^_(?:host|service)_/i', $c);
            }
        ));
        foreach ($this->getRestrictions($name) as $filter) {
            if ($filter === '*') {
                return Filter::matchAll();
            }
            try {
                $restriction->addFilter(Filter::fromQueryString($filter));
            } catch (QueryException $e) {
                throw new ConfigurationError(
                    t(
                        'Cannot apply restriction %s using the filter %s. You can only use the following columns: %s'
                    ),
                    $name,
                    $filter,
                    implode(', ', array(
                        'instance_name',
                        'host_name',
                        'hostgroup_name',
                        'service_description',
                        'servicegroup_name',
                        '_(host|service)_<customvar-name>'
                    )),
                    $e
                );
            }
        }

        if ($restriction->isEmpty()) {
            return Filter::matchAll();
        }

        return $restriction;
    }

    /**
     * Return restriction information for an eventually authenticated user
     *
     * @param   string  $name   Restriction name
     *
     * @return  array
     */
    public function getRestrictions($name)
    {
        return Auth::getInstance()->getRestrictions($name);
    }

}
