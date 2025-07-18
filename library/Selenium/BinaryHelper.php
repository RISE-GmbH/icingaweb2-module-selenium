<?php

/* originally from Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2 */
/* generated by icingaweb2-module-scaffoldbuilder | GPLv2+ */

namespace Icinga\Module\Selenium;


use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use Icinga\Module\Selenium\ProvidedHook\SeleniumServiceHealth;
use ZipArchive;


class BinaryHelper
{

    public function getChromeDriverVersion($location){
        try{
            if(file_exists($location)){
                exec(escapeshellcmd($location)." ".escapeshellarg(' --version'),$output);

                $output = implode(" ",$output);
                $output = explode(" ",$output);
                $version = $output[1];
                return $version;
            }else{
                return "0";
            }

        }catch (\Throwable $e){
            return "0";
        }


    }
    public function getChromeVersion($location){
        if(file_exists($location)){
            exec(escapeshellcmd($location)." ".escapeshellarg(' --version'),$output);
            $output = implode(" ",$output);
            $output = explode(" ",$output);
            $version = array_pop($output);
            return $version;
        }else{
            //TODO return something else but this breaks nothing
            return "not found";
        }


    }

    public function needsUpdate(){
        $binary = Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."binaries".DIRECTORY_SEPARATOR.'chromedriver';
        $version = $this->getChromeVersion("/usr/bin/google-chrome-stable");
        $driverVersion =$this->getChromeDriverVersion($binary);

        if($driverVersion === $version){
            return [false,$version,$driverVersion];
        }else{
            return [true,$version,$driverVersion];
        }
    }

    public function getVersion($version){
        $proxy = Module::get('selenium')->getConfig()->get('selenium','proxy');

        $host="https://googlechromelabs.github.io";
        $path="/chrome-for-testing/known-good-versions-with-downloads.json";
        $Connection = new HttpClient($host,null, null,null,true,$proxy);
        $Request = new Request();
        $Request->setPath($path);
        $Request->setMethod("GET");
        $Response = $Connection->request($Request);
        $filtered_data = array_filter($Response->json()['versions'], function($item) use ($version) {
            return $item['version'] == $version;
        });

        return  $filtered_data;

    }
    public function update()
    {
        $binary = Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."binaries".DIRECTORY_SEPARATOR.'chromedriver';
        $proxy = Module::get('selenium')->getConfig()->get('selenium','proxy');
        $platform ="linux64";
        $version = $this->getChromeVersion("/usr/bin/google-chrome-stable");
        if($version === "not found"){
            return false;
        }
        $driverVersion = $this->getChromeDriverVersion($binary);
        if($driverVersion === $version){
            #return true;
        }
        $data = $this->getVersion($version);
        if(count($data) > 0){
            $entry=array_pop($data);
            Logger::info("Found matching chromedriver for $version");
            foreach($entry['downloads']['chromedriver'] as $driver){
                if($driver['platform'] ==$platform){
                    $parsed_url = parse_url($driver['url']);

                    if ($parsed_url === false) {
                        echo "Failed to parse the URL.";
                        exit;
                    }
                    $scheme = $parsed_url['scheme']; // Scheme (e.g., "https")
                    $host = $parsed_url['host']; // Host (e.g., "storage.googleapis.com")
                    $path = $parsed_url['path'];
                    $filename = basename($parsed_url['path']);
                    $filepath = Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."downloads".DIRECTORY_SEPARATOR.$filename;
                    $binaries = Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."binaries".DIRECTORY_SEPARATOR.$version;
                    $currentDriver = $binaries.DIRECTORY_SEPARATOR."chromedriver-linux64".DIRECTORY_SEPARATOR."chromedriver";
                    $host =$scheme."://".$host;
                    $Connection = new HttpClient($host,null, null,null,true, $proxy);
                    $Request = new Request();
                    $Request->setPath($path);
                    $Request->setMethod("GET");
                    $Response = $Connection->download($Request,$filepath);
                    $zip = new ZipArchive;
                    $res = $zip->open($filepath);
                    if ($res === TRUE) {
                        $index = $zip->locateName('chromedriver-linux64/chromedriver'); // Locate the file within the archive
                        if ($index !== false) {
                            // Extract the file to the specified location
                            if ($zip->extractTo($binaries)) {
                                chmod($currentDriver,0755);
                                if(file_exists($binary)){
                                    unlink($binary);
                                }

                                symlink($currentDriver,$binary);
                                $service = (new SeleniumServiceHealth())->getObject();
                                exec($service->restartcmd." 2>&1", $output);
                                sleep(2);
                                return true;
                            } else {
                                return false;
                            }
                        } else {
                            return false;
                        }
                        $zip->close();
                        return false;
                    } else {
                        return false;
                    }
                }
            }



        }
        return false;
    }

}
