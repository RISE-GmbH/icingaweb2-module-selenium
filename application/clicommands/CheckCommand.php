<?php

namespace Icinga\Module\Selenium\Clicommands;

use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Cli\Command;
use Icinga\Module\Selenium\Common\Database;
use Icinga\Module\Selenium\Model\Project;
use Icinga\Module\Selenium\Model\Testsuite;
use Icinga\Module\Selenium\SuiteHelperIcingaDb;
use Icinga\Module\Selenium\SuiteHelperIdo;
use ipl\Stdlib\Filter;


class CheckCommand extends Command
{


    /**
     * Check a specific process
     *
     * USAGE
     *
     * icingacli selenium check
     *
     * OPTIONS
     *
     */

    public function defaultAction()
    {
        $db = Database::get();
        $project = $this->params->getRequired('project');
        $testsuite = $this->params->getRequired('testsuite');
        $suite_ref = $this->params->get('suite-id');
        $test_ref = $this->params->get('test-id');
        $withImages = $this->params->get('with-images') !== null;
        $removeImagesOnSuccess = $this->params->get('remove-images-on-success') !== null;
        $minimal = $this->params->get('minimal') !== null;

        $host = $this->params->get('host');
        $service = $this->params->get('service');

        $critical = $this->params->get('critical');
        $warning = $this->params->get('warning');
        $run_ref = $this->params->get('run-reference');

        $object=null;
        if($host != null){
            $object = $host;
            if($service != null){
                $object .= "!".$service;
            }
        }

        $projectModel = Project::on($db)->filter(Filter::equal('name', $project))->first();
        if($projectModel == null){
            Logger::error("Project not found\n");
            exit(3);
        }
        /* @var $testsuiteModel Testsuite */
        $testsuiteModel = Testsuite::on($db)->filter(Filter::equal('project_id', $projectModel->id))->filter(Filter::equal('name', $testsuite))->first();

        if($testsuiteModel == null){
            Logger::error("Testsuite not found\n");
            exit(3);
        }

        $object_backend = \Icinga\Application\Config::module('selenium')->getSection('backend')->get('object');
        if($object_backend === "monitoring" && Module::exists('monitoring')){
            $helper = new SuiteHelperIdo(Database::get(),$testsuiteModel,true,$withImages,$minimal,$removeImagesOnSuccess,$object);
        }else{
            $helper = new SuiteHelperIcingaDb(Database::get(),$testsuiteModel,true,$withImages,$minimal,$removeImagesOnSuccess,$object);
        }


        if($suite_ref !== null && $test_ref != null){
            $testrun = $helper->runTest($suite_ref,$test_ref,$run_ref);
        }elseif($suite_ref !== null){
            $testrun = $helper->runSuite($suite_ref,$run_ref);

        }else{
            $testrun = $helper->runAll($run_ref);
        }



        $this->evaluate($testrun,$critical,$warning);

    }
    public function evaluate($testrun,$critical,$warning){
        $exitcode = 0;
        if($testrun === false) {
            echo "[UNKNOWN] Something went wrong!\n";
            exit(3);
        }
        if($testrun->status === 'success'){
            echo "[OK] All tests passed!\n";
            $exitcode = 0;
        }else{
            echo "[CRITICAL] Not all tests passed!\n";
            $exitcode = 2;
        }
        $data = json_decode($testrun->result,true);
        $duration=0;
        if(isset($data['tests'])){
            foreach ($data['tests'] as $test){
                foreach ($test['commands'] as $command){
                    if(isset($command['duration'])){
                        $duration+=floatval($command['duration']);
                    }
                }

            }
        }

        if($this->doesViolate($duration,$warning)){
            echo "[WARNING] Duration is not in range!\n";
            $exitcode = max($exitcode,1);
        }elseif($this->doesViolate($duration,$critical)){
            echo "[CRITICAL] Duration is not in range!\n";
            $exitcode = max($exitcode,2);
        }else{
            echo "[OK] Duration is in range!\n";
            $exitcode = max($exitcode,0);
        }
        if($warning == null){
            $warning="";
        }
        if($critical == null){
            $critical="";
        }
        $ranges=rtrim(";".$warning.";".$critical,";");

        echo "| 'duration'=".number_format($duration,5,".","")."s".$ranges." 'id'=".$testrun->id."\n";
        exit($exitcode);
    }


    public function parseThresholdRange($range) {
        // Regular expression pattern for parsing threshold range
        $pattern = '/^(@?)(-?\d*|\~):?(-?\d*|\~)?$/';
        if (preg_match($pattern, $range, $matches)) {


            $invert = ($matches[1] === '@'); // Check if inverted
            $start = ($matches[2] === '') ? ($matches[1] === '~' ? '-INF' : 0) : $matches[2];
            $end = ($matches[3] === '') ? 'INF' : $matches[3];
            if(strpos($range,':') === false){
                $end = $start;
                $start =0;
            }
            // Convert start and end to integers if not infinity
            $start = ($start === '-INF') ? -PHP_FLOAT_MAX : (int)$start;
            $end = ($end === 'INF') ? PHP_FLOAT_MAX : (int)$end;

            return array('invert' => $invert, 'start' => $start, 'end' => $end);
        } else {
            return false; // Return false for invalid range format
        }
    }

    public function doesViolate($value, $range) {
        if($range === null){
            return false;
        }
        $parsedRange = $this->parseThresholdRange($range);
        if ($parsedRange === false) {
            return "Invalid range format";
        }
        // Check if value is within or outside of the range
        if ($parsedRange['invert'] == false) {
            if ($value < $parsedRange['start'] || $value > $parsedRange['end']) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($value >= $parsedRange['start'] && $value <= $parsedRange['end']) {
                return true;
            } else {
                return false;
            }
        }
    }

}

