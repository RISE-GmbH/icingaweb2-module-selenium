<?php


namespace Icinga\Module\Selenium\Clicommands;

use Icinga\Cli\Command;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Director\Objects\DirectorDatafieldCategory;
use Icinga\Module\Selenium\DirectorConfig;


class CreateCommand extends Command
{
    /**
     * USAGE:
     *
     *   icingacli selenium create command
     */
    public function commandAction()
    {
        $config = new DirectorConfig();
        $config->sync();
        if($config->commandExists($config->createCommand()) && !$config->datafieldsDiffer($config->createCommand()) ){
            echo "Commands in sync\n";
            exit(0);
        }else{
            echo "Commands not sync\n";
            exit(1);
        }

    }
    /**
     * USAGE:
     *
     *   icingacli selenium backend
     */
    public function backendAction()
    {
        $options=['icingadb','monitoring'];

        $name = $this->params->getRequired('name');
        if(in_array($name,$options)){
            $lastConfig =   $this->Config('config')->getSection('backend')->toArray();
            $lastConfig['object']=$name;
            $this->Config('config')->setSection('backend',$lastConfig)->saveIni();
            echo "ObjectBackend set successfully\n";
            exit(0);
        }else{
            echo "Only these Backends are allowed:\n\n";
            foreach ($options as $name){
                echo $name."\n";

            }
            exit(1);
        }

    }
    /**
     * USAGE:
     *
     *   icingacli selenium backend
     */
    public function resourceAction()
    {
        $options = ResourceFactory::getResourceConfigs('db')->keys();
        $name = $this->params->getRequired('name');
        if(in_array($name,$options)){
            $lastConfig =   $this->Config('config')->getSection('backend')->toArray();
            $lastConfig['resource']=$name;
            $this->Config('config')->setSection('backend',$lastConfig)->saveIni();
            echo "Resource set successfully\n";
            exit(0);
        }else{
            echo "Only these Resources are allowed:\n\n";
            foreach ($options as $name){
                echo $name."\n";

            }
            exit(1);
        }

    }
}
