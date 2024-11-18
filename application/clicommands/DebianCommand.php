<?php


namespace Icinga\Module\Selenium\Clicommands;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;

use Icinga\Cli\Command;
use Icinga\Module\Director\CheckPlugin\Threshold;
use Icinga\Module\Selenium\BinaryHelper;
use Icinga\Module\Selenium\Common\Database;


class DebianCommand extends Command
{

    /**
     * USAGE:
     *
     *   icingacli selenium debian updateChrome
     */
    public function updateChromeAction()
    {
        exec("apt-cache policy google-chrome-stable"." 2>&1", $output);

        $installed_pattern = '/Installed: (\d+\.\d+\.\d+\.\d+)-1/';
        $candidate_pattern = '/Candidate: (\d+\.\d+\.\d+\.\d+)-1/';

        preg_match($installed_pattern, implode("\n",$output), $installed_matches);
        preg_match($candidate_pattern, implode("\n",$output), $candidate_matches);

        $installed_version = $installed_matches[1];
        $candidate_version = $candidate_matches[1];

        if($installed_version !== $candidate_version){
            $a = new BinaryHelper();
            if(count($a->getVersion($candidate_version)) > 0){
                echo "Chrome can be updated, there is a webdriver for the version $candidate_version\n";
                $this->unholdChrome();
                $this->updateChrome();
                $this->holdChrome();
                if($a->update()){
                    echo "Chrome updated to $candidate_version, chrome driver updated to $candidate_version\n";
                    exit(0);
                }else{
                    echo "Something went wrong...";
                    exit(2);

                }

            }else{
                echo "Chrome can not be updated, there is no webdriver for the version $candidate_version\n";
                exit(2);
            }
        }else{
            echo "There is no update for google chrome, did you start apt-get update before?\n";
            exit(0);
        }

    }
    private function holdChrome(){
        exec("apt-mark hold google-chrome-stable"." 2>&1", $output);
        echo implode("\n",$output)."\n";
    }
    private function unholdChrome(){
        exec("apt-mark unhold google-chrome-stable"." 2>&1", $output);
        echo implode("\n",$output)."\n";
    }
    private function updateChrome(){
        exec("apt-get -y install google-chrome-stable"." 2>&1", $output);
        echo implode("\n",$output)."\n";
    }

}
