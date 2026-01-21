<?php


namespace Icinga\Module\Selenium\Clicommands;




use Icinga\Application\Modules\Module;
use Icinga\Cli\Command;
use Icinga\Module\Selenium\Common\Database;
use Icinga\Module\Selenium\TestrunHelper;

class CleanupCommand extends Command
{
    /**
     * USAGE:
     *
     *   icingacli selenium cleanup
     */
    public function testrunsAction()
    {
        $helper = new TestrunHelper(Database::get());
        $result = $helper->cleanUp();
        echo "Deleted testruns: ".$result['deletedTestruns']."\n";
        echo "Deleted images: ".$result['deletedImages']."\n";
    }
    /**
     * USAGE:
     *
     *   icingacli selenium cleanup
     */
    public function processesAction()
    {
        $basedir = Module::get('selenium')->getBaseDir();
        $script = $basedir.'/contrib/bin/kill_old_chrome.sh';


        $command = "$script 2>&1";


        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        echo implode("\n", $output)."\n";

        if ($returnVar !== 0) {
            error_log("kill_old_chrome.sh failed with code $returnVar");
        }
    }
}
