<?php


namespace Icinga\Module\Selenium\Clicommands;

use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Modules\Module;

use Icinga\Cli\Command;
use Icinga\Module\Director\CheckPlugin\Threshold;
use Icinga\Module\Selenium\BinaryHelper;
use Icinga\Module\Selenium\Common\Database;


class InitCommand extends Command
{
    /**
     * USAGE:
     *
     *   icingacli selenium init
     */
    public function defaultAction()
    {
        if($this->params->get('with-commands') !== null){
            if($fieldCat = $this->params->getRequired('field-category')){
                $this->Config('director')->setSection('datafield',['category_id']);
            }
        }
        $folders =[
            Module::get("selenium")->getConfigDir().DIRECTORY_SEPARATOR."binaries",
            Module::get("selenium")->getConfigDir().DIRECTORY_SEPARATOR."images",
            Module::get("selenium")->getConfigDir().DIRECTORY_SEPARATOR."downloads",
        ];
        foreach($folders as $folder){
            if(!file_exists($folder)){
                mkdir($folder,0755,true);
            }
        }
        $a = new BinaryHelper();
        $a->update();

    }

}
