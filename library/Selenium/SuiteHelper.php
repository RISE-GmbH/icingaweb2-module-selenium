<?php

// SPDX-FileCopyrightText: 2026 Research Industrial Systems Engineering (RISE) Forschungs-, Entwicklungs- und Großprojektberatung GmbH
// SPDX-License-Identifier: GPL-3.0-or-later


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
use InvalidArgumentException;
use ipl\Sql\Connection;
use RuntimeException;

class SuiteHelper
{

    protected $db;
    protected $suite;
    protected $data;
    /* @var RemoteWebDriver */
    protected $driver;

    protected $checkSource;
    protected $imagepath;
    protected $autoClose;
    protected $withImages;
    protected $removeImagesOnSuccess;
    protected $minimal;
    protected $http_proxy = null;
    protected $http_proxy_port = null;
    public static function supportedCommands()
    {
        return ['echo', 'if', 'elseif', 'else if', 'else', 'end', 'close', 'executeScript', 'pause', 'verifyText', 'setWindowSize', 'open', 'type', 'click', 'waitForElementNotPresent', 'waitForElementPresent', 'assertElementNotPresent', 'assertElementPresent', 'verifyElementNotPresent', 'verifyElementPresent'];
    }


    public function webDriverByHelper($target)
    {
        $target = explode("=", $target, 2); // the string can contain =
        $type = $target[0];
        $identifier = $target[1];

        if ($type === "name") {
            $driverBy = WebDriverBy::name($identifier);
        } elseif ($type === "id") {
            $driverBy = WebDriverBy::id($identifier);
            #Logger::error(implode("=",$target)." was ".$type." ".$identifier);
        } elseif ($type === "xpath") {
            $driverBy = WebDriverBy::xpath($identifier);
        } elseif ($type === "css") {
            $driverBy = WebDriverBy::cssSelector($identifier);
        } elseif ($type === "linkText") {
            $driverBy = WebDriverBy::linkText($identifier);
        } else {
            throw new \Exception("unsupported: " . print_r($target, true));
        }
        return $driverBy;

    }

    public function findElement($target, $driver)
    {
        $driverBy = $this->webDriverByHelper($target);
        try {
            $element = $driver->findElement($driverBy);
        } catch (\Throwable $e) {
            $element = null;
        }

        return $element;
    }

    public function __construct(Connection $db, Testsuite $suite, $autoClose = true, $withImages = true, $minimal = false, $removeImagesOnSuccess = false, $reference_object = null, $override_check_source = null)
    {
        $this->checkSource = $override_check_source;

        if($this->checkSource === null){
            $this->checkSource = trim(shell_exec('hostname -f'));
        }

        // existing constructor code...

        // register a shutdown cleanup
        register_shutdown_function(function () {
            // If shutdown is due to fatal error, fetch it
            $error = error_get_last();
            if ($error !== null) {
                Logger::error("SuiteHelper shutdown due to fatal error: " . $error['message']);
            }

            // Ensure WebDriver is closed properly if still open
            $this->savePowerOff();
        });

        // catch TERM/INT (but not SIGKILL)
        if (function_exists('pcntl_signal')) {
            declare(ticks=1);
            pcntl_signal(SIGTERM, function () {
                Logger::info("Received SIGTERM, shutting down SuiteHelper...");
                exit;
            });
            pcntl_signal(SIGINT, function () {
                Logger::info("Received SIGINT (Ctrl+C), shutting down SuiteHelper...");
                exit;
            });
        }

        $this->removeImagesOnSuccess = $removeImagesOnSuccess;
        $this->imagepath = Module::get('selenium')->getConfig('config')->getSection('images')->get('path', Module::get('selenium')->getConfigDir() . DIRECTORY_SEPARATOR . "images");

        $proxy = $suite->proxy;


        if ($proxy != null && $proxy != "") {
            $proxyArray = explode(":", $proxy);
            if (count($proxyArray) == 2) {
                $this->http_proxy = $proxyArray[0];
                $this->http_proxy_port = $proxyArray[1];
            }
        }


        $capabilities = DesiredCapabilities::chrome();
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments(['--headless=new']);
        $chromeOptions->addArguments(['--disable-dev-shm-usage']);
        $chromeOptions->addArguments(['--no-sandbox']);
        $chromeOptions->addArguments(['--disable-gpu']);
        $chromeOptions->addArguments(['--disable-software-rasterizer']);
        $chromeOptions->addArguments(['--disable-extensions']);
        $chromeOptions->addArguments(['--disable-background-networking']);
        $chromeOptions->addArguments(['--disable-background-timer-throttling']);
        $chromeOptions->addArguments(['--disable-sync']);
        $chromeOptions->addArguments(['--mute-audio']);
        $chromeOptions->addArguments(['--memory-pressure-off']);
        $chromeOptions->addArguments([
            '--disable-breakpad',
            '--disable-crash-reporter',
            '--disable-in-process-stack-traces',
        ]);
        //$chromeOptions->addArguments(['--metrics-recording-only']);

        $chromeOptions->addArguments(['--remote-debugging-pipe']);
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        $this->autoClose = $autoClose;
        $this->minimal = $minimal;
        $this->withImages = $withImages;

        $capabilities->setCapability('acceptInsecureCerts', true);
        $this->driver = RemoteWebDriver::create('http://127.0.0.1:9515/', $capabilities, null, null, $this->http_proxy, $this->http_proxy_port);

        if (!$this->checkAndCreateFolder($this->imagepath)) {
            throw new \Exception("Can't create or access folder " . $this->imagepath);
        }
    }

    public function close()
    {
        $this->driver->close();
    }

    public function runTest($suite_ref, $test_ref, $run_ref = null)
    {
        foreach ($this->data['suites'] as $suite) {
            if ($suite['id'] === $suite_ref) {
                if (in_array($test_ref, $suite['tests'])) {
                    foreach ($this->data['tests'] as $test) {
                        if ($test['id'] === $test_ref) {
                            $testrun = new Testrun();
                            $testrun->ctime = new \DateTime();
                            $testrun->name = "SingleTest-" . $test['name'] . "-" . $test_ref;
                            $testrun->status = "running";
                            $testrun->run_ref = $run_ref;
                            $testrun->check_source = $this->checkSource;
                            $testrun->result = "";
                            $testrun->project_id = $this->suite->project_id;
                            $testrun->testsuite_id = $this->suite->id;
                            $testrun->save();
                            $result = $this->run([$test_ref], $testrun);

                            $testrun->result = json_encode($result);


                            $testrun->mtime = new \DateTime();
                            if ($result['success'] === true) {
                                $testrun->status = "success";
                            } else {
                                $testrun->status = "failed";
                            }
                            $testrun->save();
                            return $testrun;

                        }
                    }

                } else {
                    return false;
                }
            }
        }
        return false;
    }

    public function runSuite($suite_ref, $run_ref = null)
    {
        foreach ($this->data['suites'] as $suite) {
            if ($suite['id'] === $suite_ref) {

                $testrun = new Testrun();
                $testrun->ctime = new \DateTime();
                $testrun->name = "Suite-Test" . $suite['name'] . "-all";
                $testrun->status = "running";
                $testrun->run_ref = $run_ref;
                $testrun->check_source = $this->checkSource;
                $testrun->result = "";
                $testrun->project_id = $this->suite->project_id;
                $testrun->testsuite_id = $this->suite->id;
                $testrun->save();
                $result = $this->run($suite['tests'], $testrun);

                $testrun->result = json_encode($result);
                $testrun->mtime = new \DateTime();
                if ($result['success'] === true) {
                    $testrun->status = "success";
                } else {
                    $testrun->status = "failed";
                }
                $testrun->save();
                return $testrun;

            }
        }

        return false;
    }

    public function runAll($run_ref = null)
    {
        $allTests = [];
        foreach ($this->data['tests'] as $test) {
            $allTests[] = $test['id'];
        }
        $testrun = new Testrun();
        $testrun->ctime = new \DateTime();
        $testrun->name = "AllSuites_" . $this->data['name'];
        $testrun->status = "running";
        $testrun->run_ref = $run_ref;
        $testrun->check_source = $this->checkSource;
        $testrun->result = "";
        $testrun->project_id = $this->suite->project_id;
        $testrun->testsuite_id = $this->suite->id;
        $testrun->save();

        $result = $this->run($allTests, $testrun);

        $testrun->result = json_encode($result);
        $testrun->mtime = new \DateTime();
        if ($result['success'] === true) {
            $testrun->status = "success";
        } else {
            $testrun->status = "failed";
        }
        $testrun->save();
        return $testrun;

    }

    public function run($tests, Testrun $testrun)
    {

        $driver = $this->driver;
        $driver->manage()->timeouts()->implicitlyWait(floatval($this->suite->implicit_wait));
        $images = [];
        $success = true;

        $currentData = json_decode(json_encode($this->data), true); //clone

        foreach ($currentData['tests'] as $testKey => $test) {
            if (in_array($test['id'], $tests)) {

                $flowStack = [];
                $skipBlock = false;
                $vars = [];
                $canceled = false;
                $test['planned'] = true;

                foreach ($test['commands'] as $commandKey => $command) {
                    $start = microtime(true);
                    $command['duration'] = 0;
                    $command['status'] = 'ok';
                    $command['reason'] = '';
                    $cmd = strtolower($command['command']);

                    $taskTitle = "Executing: " . $command['command'] . " on " . $command['target'];
                    $val = $command['value'];
                    if ($cmd === "type" && strpos($command['target'], "password") !== false) {
                        $val = "********";
                    }
                    if (!empty($command['value'])) {
                        $taskTitle .= " with " . $val;
                    }
                    $command['title'] = $taskTitle;
                    if ($this->driver === null) {
                        $command['status'] = 'failed';
                        $test['commands'][$commandKey] = $command;
                        $success = false;
                        continue;
                    }
                    if ($canceled) {
                        $command['status'] = 'canceled';
                        $test['commands'][$commandKey] = $command;

                        continue;
                    }


                    $success = true;


                    // 🧠 Control flow handling
                    switch ($cmd) {

                        case 'if':
                            if (!$skipBlock) {
                                $expr = $command['target'];
                                foreach ($vars as $k => $v) {
                                    $expr = str_replace('${' . $k . '}', json_encode($v), $expr);
                                }

                                try {
                                    $result = (bool) $driver->executeScript("return !!($expr)");
                                } catch (\Throwable $e) {
                                    $result = false;
                                }

                                $flowStack[] = [
                                    'executed' => $result,
                                    'skipping' => !$result,
                                    'parentSkipped' => false, // 🔧 FIX
                                ];
                            } else {
                                $flowStack[] = [
                                    'executed' => false,
                                    'skipping' => true,
                                    'parentSkipped' => true, // 🔧 FIX
                                ];
                                $command['status'] = 'skipped';
                            }

                            $skipBlock = end($flowStack)['skipping'];
                            break;

                        case 'elseif':
                        case 'else if':
                            $current = array_pop($flowStack);

                            if ($current['executed'] || $current['parentSkipped']) { // 🔧 FIX
                                $current['skipping'] = true;
                            } else {
                                $expr = $command['target'];
                                foreach ($vars as $k => $v) {
                                    $expr = str_replace('${' . $k . '}', json_encode($v), $expr);
                                }

                                try {
                                    $result = (bool) $driver->executeScript("return !!($expr)");
                                } catch (\Throwable $e) {
                                    $result = false;
                                }

                                $current['executed'] = $result;
                                $current['skipping'] = !$result;
                            }

                            $flowStack[] = $current;
                            $skipBlock = $current['skipping'];
                            if ($skipBlock) {
                                $command['status'] = 'skipped';
                            }
                            break;

                        case 'else':
                            $current = array_pop($flowStack);

                            if ($current['executed'] || $current['parentSkipped']) { // 🔧 FIX
                                $current['skipping'] = true;
                            } else {
                                $current['executed'] = true;
                                $current['skipping'] = false;
                            }

                            $flowStack[] = $current;
                            $skipBlock = $current['skipping'];
                            if ($skipBlock) {
                                $command['status'] = 'skipped';
                            }
                            break;

                        case 'end':
                            if (empty($flowStack)) {
                                throw new \RuntimeException("Unexpected end without if");
                            }

                            $closed = array_pop($flowStack);

                            // ✅ end is skipped ONLY if the IF itself was skipped by a parent
                            if ($closed['parentSkipped']) {
                                $command['status'] = 'skipped';
                            } else {
                                $command['status'] = 'ok';
                            }

                            // recompute skipBlock from remaining stack
                            $skipBlock = false;
                            foreach ($flowStack as $frame) {
                                if ($frame['skipping']) {
                                    $skipBlock = true;
                                    break;
                                }
                            }
                            break;

                        default:
                            if ($skipBlock) {
                                $command['status'] = 'skipped';
                                break;
                            }



                            try {
                                switch ($cmd) {
                                    case 'open':
                                        $currentUrl = $currentData['url'] . $command['target'];
                                        if (strpos($command['target'], "http") === 0) {
                                            $currentUrl = $command['target'];
                                        }
                                        $this->checkUrl($currentUrl,$this->http_proxy, $this->http_proxy_port);
                                        $driver->get($currentUrl);
                                        break;

                                    case 'executescript':
                                        $result = $driver->executeScript($command['target']);
                                        if (!empty($command['value'])) {
                                            $vars[$command['value']] = $result;
                                        }
                                        break;

                                    case 'type':
                                        $element = $driver->findElement($this->webDriverByHelper($command['target']));
                                        $element->sendKeys($command['value']);
                                        break;
                                    case 'pause':
                                        sleep(intval($command['target']) / 1000);
                                        break;

                                    case 'click':
                                        $element = $driver->findElement($this->webDriverByHelper($command['target']));
                                        $element->click();
                                        break;

                                    case 'waitforelementnotpresent':
                                        $driver->wait(intval($command['value']) / 1000)
                                            ->until(WebDriverExpectedCondition::not(
                                                WebDriverExpectedCondition::presenceOfElementLocated(
                                                    $this->webDriverByHelper($command['target'])
                                                )
                                            ));
                                        break;

                                    case 'waitforelementpresent':
                                        $driver->wait(intval($command['value']) / 1000)
                                            ->until(WebDriverExpectedCondition::presenceOfElementLocated(
                                                $this->webDriverByHelper($command['target'])
                                            ));
                                        break;

                                    case 'verifytext':
                                        $element = $driver->findElement($this->webDriverByHelper($command['target']));
                                        $text = trim($element->getText());
                                        $expected = trim($command['value']);
                                        if ($text !== $expected) {
                                            throw new \RuntimeException("$expected does not match $text");
                                        }
                                        break;

                                    case 'assertelementnotpresent':
                                        try {
                                            // Element should NOT exist — wait briefly and confirm absence
                                            $driver->wait(1)->until(
                                                WebDriverExpectedCondition::not(
                                                    WebDriverExpectedCondition::presenceOfElementLocated(
                                                        $this->webDriverByHelper($command['target'])
                                                    )
                                                )
                                            );
                                        } catch (\Throwable $e) {
                                            $command['status'] = 'failed';
                                            $command['reason'] = $e->getMessage();
                                            $success = false;
                                            $canceled = true; // 🔴 assert fails -> stop test
                                        }
                                        break;


                                    case 'verifyelementnotpresent':
                                        try {
                                            $driver->wait(1)->until(
                                                WebDriverExpectedCondition::not(
                                                    WebDriverExpectedCondition::presenceOfElementLocated(
                                                        $this->webDriverByHelper($command['target'])
                                                    )
                                                )
                                            );
                                        } catch (\Throwable $e) {
                                            $command['status'] = 'failed';
                                            $command['reason'] = $e->getMessage();
                                            $success = false;
                                            // ⚠️ verify fails -> continue test
                                        }
                                        break;

                                    case 'assertelementpresent':
                                        try {
                                            $driver->wait(1)->until(WebDriverExpectedCondition::presenceOfElementLocated(
                                                $this->webDriverByHelper($command['target'])
                                            ));
                                        } catch (\Throwable $e) {
                                            $command['status'] = 'failed';
                                            $command['reason'] = $e->getMessage();
                                            $success = false;
                                            $canceled = true;  // stop the test
                                        }
                                        break;

                                    case 'verifyelementpresent':
                                        try {
                                            $driver->wait(1)->until(WebDriverExpectedCondition::presenceOfElementLocated(
                                                $this->webDriverByHelper($command['target'])
                                            ));
                                        } catch (\Throwable $e) {
                                            $command['status'] = 'failed';
                                            $command['reason'] = $e->getMessage();
                                            $success = false;
                                            // do NOT cancel test
                                        }
                                        break;

                                    case 'setwindowsize':
                                        [$width, $height] = explode("x", $command['target']);
                                        $driver->execute(DriverCommand::SET_WINDOW_SIZE, [
                                            'width' => intval($width),
                                            'height' => intval($height),
                                            ':windowHandle' => 'current',
                                        ]);
                                        break;
                                    case 'close':
                                        $driver->close();
                                        $this->savePowerOff();
                                        break;
                                    case 'echo':
                                        $command['reason'] = $command['target'];
                                        break;
                                    default:
                                        Logger::error('Command unsupported: ' . $command['command']);
                                        break;
                                }
                            } catch (\Throwable $e) {
                                $command['status'] = 'failed';
                                $command['reason'] = $e->getMessage();
                                $success = false;
                                $canceled = true;
                            }

                            if ($cmd === "type" && strpos($command['target'], "password") !== false) {
                                $command['value'] = "********";
                            }

                            usleep(floatval($this->suite->sleep) * 1000000);
                            break;
                    }


                    if ($this->withImages && !$this->minimal && $command['status'] !== 'canceled' && $cmd !== "close") {
                        $command['img'] = $this->imagepath . DIRECTORY_SEPARATOR . $testrun->id . "-" . time() . "-" . $command['id'] . ".png";
                        $images[] = $command['img'];
                        $driver->takeScreenshot($command['img']);
                    }

                    $command['duration'] = microtime(true) - $start;
                    $test['commands'][$commandKey] = $command;
                }

            } else {
                $test['planned'] = false;
            }

            $currentData['tests'][$testKey] = $test;
        }


        //$currentData['tests'][$testKey]=$test;


        if ($this->minimal) {
            $currentData = [];
        }
        $currentData['success'] = $success;
        if ($this->autoClose) {
            $this->savePowerOff();

        }

        if ($success && $this->removeImagesOnSuccess) {
            foreach ($images as $image) {
                unlink($image);
            }

        }
        return $currentData;
    }

    public function checkAndCreateFolder($folder)
    {
        if (!file_exists($folder)) {
            try {
                mkdir($folder, 0755, true);
            } catch (\Throwable $e) {
                return false;
            }

        }

        if (!is_writable($folder)) {
            return false;
        }
        return true;
    }

    protected function savePowerOff()
    {
        try {
            $this->driver->get('chrome://quit');
        } catch (\Throwable $e) {
            // ignore — browser exit causes invalid session
        }

        try {
            $this->driver->quit();
        } catch (\Throwable $e) {
            // ignore — session is already dead
        }

        $this->driver = null;
    }
    protected function checkUrl(string $url, ?string $proxy = null, ?int $proxyPort = null): void
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            throw new InvalidArgumentException("Invalid URL: $url");
        }

        $host = $parts['host'];

        // Resolve only to verify DNS works
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            $resolvedIp = gethostbyname($host);

            if ($resolvedIp === $host) {
                throw new RuntimeException("DNS resolution failed for host: $host");
            }
        }

        $ch = curl_init($url);
        if ($ch === false) {
            Logger::error('Failed to initialize cURL');
            return;
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($proxy !== null) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);

            if ($proxyPort !== null) {
                curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
            }
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            throw new RuntimeException("cURL error ($errno): $error");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new RuntimeException("HTTP request failed with status code $httpCode");
        }


    }

    protected function applyOverrideFile($data, $filepath){
        if($filepath === null || $filepath === ""){
            return $data;
        }

        if(!file_exists($filepath)){
            Logger::error("Selenium: File $filepath does not exist");
            return $data;
        }

        if(!is_readable($filepath)){
            Logger::error("Selenium: File $filepath is not readable");
            return $data;
        }

        try {
            $json = json_decode(file_get_contents($filepath), true);
            foreach ($json as $key => $value) {
                if (preg_match('@^\$([^$]+)\$$@', $key, $matches)) {
                    $data = str_replace($key, $value, $data);
                }else{
                    Logger::error("Selenium: Key $key is not a vaild Macro");

                }
            }
        }catch (\Throwable $e){
            Logger::error($e->getMessage());
        }

        return $data;
    }
}
