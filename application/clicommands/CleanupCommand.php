<?php


namespace Icinga\Module\Selenium\Clicommands;




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
    public function cleanupAction()
    {
        $helper = new TestrunHelper(Database::get());
        $result = $helper->cleanUp();
        echo "Deleted testruns: ".$result['deletedTestruns']."\n";
        echo "Deleted images: ".$result['deletedImages']."\n";
    }
}
