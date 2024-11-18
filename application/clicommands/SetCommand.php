<?php


namespace Icinga\Module\Selenium\Clicommands;

use Icinga\Application\Modules\Module;
use Icinga\Cli\Command;
use Icinga\Data\ResourceFactory;
use Icinga\Module\Director\Objects\DirectorDatafieldCategory;
use Icinga\Module\Selenium\DirectorConfig;


class SetCommand extends Command
{
    /**
     * USAGE:
     *
     *   icingacli selenium set category
     */
    public function categoryAction()
    {
        $config = new DirectorConfig();
        $categories = DirectorDatafieldCategory::loadAll($config->db());
        $options=[];
        foreach ($categories as $category){
            $options[$category->get('id')]=$category->get('category_name');
        }
        $name = $this->params->getRequired('name');
        if(in_array($name,$options)){
            $flipped = array_flip($options);
            $this->Config('director')->setSection('datafield',['category_id'=>$flipped[$name]])->saveIni();
            echo "Category set successfully\n";
            exit(0);
        }else{
            echo "Only these categories are allowed:\n\n";
            foreach ($options as $name){
                echo $name."\n";

            }
            exit(1);
        }

    }

    /**
     * USAGE:
     *
     *   icingacli selenium set imagesettings
     */
    public function imagesettingsAction()
    {

        $path = $this->params->get('image_path',Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."images");
        $retentionpolicy = $this->params->get('image_retentionpolicy',30);
        $lastConfig =   $this->Config('config')->getSection('images')->toArray();
        $lastConfig['path']=$path;
        $lastConfig['retentionpolicy']=$retentionpolicy;
        $this->Config('config')->setSection('images',$lastConfig)->saveIni();
        echo "Image settings set successfully\n";
        exit(0);

    }
    /**
     * USAGE:
     *
     *   icingacli selenium set imagesettings
     */
    public function testrunsettingsAction()
    {

        $retentionpolicy = $this->params->get('testrun_retentionpolicy',30);
        $lastConfig =   $this->Config('config')->getSection('testruns')->toArray();
        $lastConfig['retentionpolicy']=$retentionpolicy;
        $this->Config('config')->setSection('testruns',$lastConfig)->saveIni();
        echo "Testrun settings set successfully\n";
        exit(0);

    }
    /**
     * USAGE:
     *
     *   icingacli selenium set backend
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
     *   icingacli selenium set resource
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
