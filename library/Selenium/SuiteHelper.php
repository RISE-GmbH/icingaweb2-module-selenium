<?php

/* originally from Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2 */
/* generated by icingaweb2-module-scaffoldbuilder | GPLv2+ */


namespace Icinga\Module\Selenium;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverTimeouts;
use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Module\Selenium\Model\Testrun;
use Icinga\Module\Selenium\Model\Testsuite;
use Icinga\Web\Notification;
use ipl\Sql\Connection;

class SuiteHelper
{

    protected $db;
    protected $suite;
    protected $data;
    /* @var RemoteWebDriver */
    protected $driver;
    protected $imagepath;
    protected $autoClose;
    protected $withImages;
    protected $removeImagesOnSuccess;
    protected $minimal;

    public static function supportedCommands(){
        return ['setWindowSize','open', 'type','click','waitForElementNotPresent','waitForElementPresent','assertElementNotPresent','assertElementPresent','verifyElementNotPresent','verifyElementPresent'];
    }


    public function webDriverByHelper($target){
        $target = explode("=",$target,2); // the string can contain =
        $type = $target[0];
        $identifier =$target[1];

        if($type === "name"){
            $driverBy = WebDriverBy::name($identifier);
        }elseif($type === "id"){
            $driverBy = WebDriverBy::id($identifier);
            #Logger::error(implode("=",$target)." was ".$type." ".$identifier);
        }elseif($type === "xpath"){
            $driverBy = WebDriverBy::xpath($identifier);
        }elseif($type === "css"){
            $driverBy =  WebDriverBy::cssSelector($identifier);
        }elseif($type === "linkText"){
            $driverBy =  WebDriverBy::linkText($identifier);
        }else{
            throw new \Exception("unsupported: ".$target);
        }
        return $driverBy;

    }

    public function findElement($target,$driver){
        $driverBy = $this->webDriverByHelper($target);
        try {
            $element = $driver->findElement($driverBy);
        }catch (\Throwable $e){
            $element = null;
        }

        return $element;
    }
    public function __construct(Connection $db, Testsuite $suite,$autoClose=true,$withImages=true,$minimal=false,$removeImagesOnSuccess =false,$reference_object=null)
    {

        $this->removeImagesOnSuccess=$removeImagesOnSuccess;
        $this->imagepath=Module::get('selenium')->getConfig('config')->getSection('images')->get('path',Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."images");

        $proxy=Module::get('selenium')->getConfig('config')->getSection('settings')->get('proxy');
        $http_proxy=null;
        $http_proxy_port=null;
        if($proxy != null && $proxy != ""){
            $proxyArray = explode(":",$proxy);
            if(count($proxyArray) == 2){
                $http_proxy=$proxyArray[0];
                $http_proxy_port=$proxyArray[1];
            }
        }


        $capabilities = DesiredCapabilities::chrome();
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless']);
        $chromeOptions->addArguments(['--disable-dev-shm-usage']);
        $chromeOptions->addArguments(['--no-sandbox']);
        $chromeOptions->addArguments(['--remote-debugging-pipe']);
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $this->autoClose=$autoClose;
        $this->minimal=$minimal;
        $this->withImages=$withImages;

        $capabilities->setCapability('acceptInsecureCerts', true);
        $this->driver = RemoteWebDriver::create('http://127.0.0.1:9515/',$capabilities,null,null,$http_proxy,$http_proxy_port);

        if(! $this->checkAndCreateFolder($this->imagepath)){
            throw new \Exception("Can't create or access folder ".$this->imagepath);
        }
    }
    public function close(){
        $this->driver->close();
    }
    public function runTest($suite_ref, $test_ref, $run_ref=null){
        foreach ($this->data['suites'] as $suite){
            if($suite['id'] === $suite_ref){
                if( in_array($test_ref,$suite['tests'])){
                     foreach ($this->data['tests'] as $test){
                         if($test['id'] === $test_ref){
                             $testrun = new Testrun();
                             $testrun->ctime=new \DateTime();
                             $testrun->name = "SingleTest-".$test['name']."-".$test_ref;
                             $testrun->status="running";
                             $testrun->run_ref=$run_ref;
                             $testrun->result="";
                             $testrun->project_id=$this->suite->project_id;
                             $testrun->testsuite_id=$this->suite->id;
                             $testrun->save();
                             $result =$this->run([$test_ref],$testrun);

                             $testrun->result= json_encode($result);


                             $testrun->mtime=new \DateTime();
                             if($result['success'] === true){
                                 $testrun->status="success";
                             }else{
                                 $testrun->status="failed";
                             }
                             $testrun->save();
                             return $testrun;

                         }
                     }

                }else{
                    return false;
                }
            }
        }
        return false;
    }
    public function runSuite($suite_ref, $run_ref=null){
        foreach ($this->data['suites'] as $suite){
            if($suite['id'] === $suite_ref){

                $testrun = new Testrun();
                $testrun->ctime=new \DateTime();
                $testrun->name = "Suite-Test".$suite['name']."-all";
                $testrun->status="running";
                $testrun->run_ref=$run_ref;
                $testrun->result="";
                $testrun->project_id=$this->suite->project_id;
                $testrun->testsuite_id=$this->suite->id;
                $testrun->save();
                $result =$this->run($suite['tests'],$testrun);

                $testrun->result= json_encode($result);
                $testrun->mtime=new \DateTime();
                if($result['success'] === true){
                    $testrun->status="success";
                }else{
                    $testrun->status="failed";
                }
                $testrun->save();
                return $testrun;

            }
        }

        return false;
    }

    public function runAll($run_ref=null){
        $allTests =[];
        foreach ($this->data['tests'] as $test){
            $allTests[]=$test['id'];
        }
        $testrun = new Testrun();
        $testrun->ctime=new \DateTime();
        $testrun->name = "AllSuites_".$this->data['name'];
        $testrun->status="running";
        $testrun->run_ref=$run_ref;
        $testrun->result="";
        $testrun->project_id=$this->suite->project_id;
        $testrun->testsuite_id=$this->suite->id;
        $testrun->save();

        $result =$this->run($allTests,$testrun);

        $testrun->result= json_encode($result);
        $testrun->mtime=new \DateTime();
        if($result['success'] === true){
            $testrun->status="success";
        }else{
            $testrun->status="failed";
        }
        $testrun->save();
        return $testrun;

    }
    public function run($tests, Testrun $testrun){

        $driver=$this->driver;
        $driver->manage()->timeouts()->implicitlyWait(floatval($this->suite->implicit_wait));
        $images=[];
        $success =true;

        $currentData = json_decode(json_encode($this->data),true); //clone

        foreach($currentData['tests'] as $testKey=>$test){
            if(in_array($test['id'],$tests)){
                $canceled = false;
                foreach ($test['commands'] as $commandKey=>$command){
                    $start = microtime(true);
                    $test['planned']=true;


                    $command['duration']=0;
                    $command['status']='ok';
                    if($canceled){
                        $command['status']='canceled';
                    }
                    $command['reason']='';
                    if(! $canceled){
                        if($command['command'] == "open"){
                            try {
                                $response = $driver->get($currentData['url'].$command['target']);
                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage();
                                $success =false;
                            }

                        }elseif($command['command'] == "type") {
                            try {
                                $element = $driver->findElement($this->webDriverByHelper($command['target']));
                                $response = $element->sendKeys($command['value']);
                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage();
                                $success =false;

                            }
                        }elseif($command['command'] == "click"){
                            try {
                                $element = $driver->findElement($this->webDriverByHelper($command['target']));

                                $response = $element->click();
                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage();
                                $success =false;

                            }

                        }elseif($command['command'] == "waitForElementNotPresent"){

                            $target = $command['target'];

                            try {
                                $response = $driver->wait(intval($command['value'])/1000)->until(WebDriverExpectedCondition::not(WebDriverExpectedCondition::presenceOfElementLocated($this->webDriverByHelper($target))));
                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage().$e->getTraceAsString();
                                $success =false;

                            }

                        }elseif($command['command'] == "waitForElementPresent"){

                            $target = $command['target'];

                            try {
                                $response = $driver->wait(intval($command['value'])/1000)->until(WebDriverExpectedCondition::presenceOfElementLocated($this->webDriverByHelper($target)));
                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage().$e->getTraceAsString();
                                $success =false;
                                $canceled = true;
                            }

                        }elseif($command['command'] == "assertElementNotPresent" || $command['command'] == "verifyElementNotPresent"){

                            $target = $command['target'];

                            try {
                                $response = $driver->wait(1)->until(WebDriverExpectedCondition::not(WebDriverExpectedCondition::presenceOfElementLocated($this->webDriverByHelper($target))));
                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage().$e->getTraceAsString();
                                $success =false;
                                //if($command['command'] == "assertElementNotPresent"){
                                    //$canceled = true;
                               // }
                            }

                        }elseif($command['command'] == "assertElementPresent" || $command['command'] == "verifyElementPresent"){

                            $target = $command['target'];

                            try {
                                $response = $driver->wait(1)->until(WebDriverExpectedCondition::presenceOfElementLocated($this->webDriverByHelper($target)));
                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage().$e->getTraceAsString();
                                $success =false;
                                //if($command['command'] == "assertElementPresent"){
                                //    $canceled = true;
                                //}
                            }

                        }elseif($command['command'] == "setWindowSize"){
                            $target = $command['target'];
                            $target = explode("x",$target);
                            try {
                                $response = $driver->execute(DriverCommand::SET_WINDOW_SIZE,['width'=>intval($target[0]), 'height'=>intval($target[1]),':windowHandle' => 'current',] );


                            }catch (\Throwable $e){
                                $command['status']="failed";
                                $command['reason']=$e->getMessage().$e->getTraceAsString();
                                $success =false;

                            }



                        }else{
                            Logger::error('Command unsupported: '.$command['command']);
                        }
                        if($success==false){
                            $canceled=true;
                        }

                        if($command['command'] == "type" && strpos($command['target'],"password")!== false){
                            $command['value'] = "********";
                        }
                        sleep($this->suite->sleep);
                    }

                    $taskTitle="Executing: ".$command['command']." on ".$command['target'];
                    if($command['value'] != "" && $command['value'] != null){
                        $taskTitle.=" with ".$command['value'];
                    }
                    $command['title']=$taskTitle;



                    if($this->withImages && !$this->minimal && $command['status']!='canceled'){
                        $command['img'] = $this->imagepath.DIRECTORY_SEPARATOR.$testrun->id."-".time()."-".$command['id'].".png";
                        $images[]=$command['img'];
                        $driver->takeScreenshot($command['img']);
                    }
                    $command['duration'] = microtime(true) - $start;

                    $test['commands'][$commandKey]=$command;
                }
            }else{
                $test['planned']=false;
            }
            $currentData['tests'][$testKey]=$test;

        }
        if($this->minimal){
            $currentData=[];
        }
        $currentData['success'] =$success;
        if($this->autoClose){
            $driver->quit();
        }

        if($success && $this->removeImagesOnSuccess){
            foreach ($images as $image){
                unlink($image);
            }

        }
        return $currentData;
    }

    public function checkAndCreateFolder($folder){
        if (!file_exists($folder)) {
            try {
                mkdir($folder, 0755, true);
            }catch (\Throwable $e){
                return false;
            }

        }

        if(!is_writable($folder)){
            return false;
        }
        return true;
    }
}
