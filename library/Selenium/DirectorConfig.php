<?php
/* originally from Icinga Web 2 Jira Module | (c) Icinga GmbH | GPLv2 */

namespace Icinga\Module\Selenium;

use Icinga\Application\Config;
use Icinga\Module\Director\DataType\DataTypeBoolean;
use Icinga\Module\Director\DataType\DataTypeString;
use Icinga\Module\Director\Db;
use Icinga\Module\Director\Objects\DirectorDatafield;
use Icinga\Module\Director\Objects\DirectorDatafieldCategory;
use Icinga\Module\Director\Objects\IcingaCommand;
use Icinga\Module\Director\Objects\IcingaCommandField;
use Icinga\Module\Director\Web\Form\IcingaObjectFieldLoader;

class DirectorConfig
{
    /** @var Db */
    protected $db;

    public function commandExists(IcingaCommand $command)
    {
        return IcingaCommand::exists($command->getObjectName(), $this->db);
    }

    public function commandDiffers(IcingaCommand $command)
    {
        return IcingaCommand::load($command->getObjectName(), $this->db)
            ->replaceWith($command)
            ->hasBeenModified();
    }

    public function datafieldsDiffer(IcingaCommand $command)
    {
        $command = IcingaCommand::load($command->getObjectName(), $this->db) ?? $command;
        $changes = $this->syncDatafields($command,false);

        return $changes['outOfSync'];

    }

    public function sync()
    {
        $commandsWasSynced = $this->syncCommand($this->createCommand());
        $datafieldsWereSynced =false;
        if(!$commandsWasSynced){
            $this->syncDatafields($this->createCommand(),false);
        }
        if($this->syncDatafields($this->createCommand(),false)['outOfSync']){
            $this->syncDatafields($this->createCommand(),true);
            $datafieldsWereSynced =true;

        }
        return $commandsWasSynced || $datafieldsWereSynced;

    }

    public function syncCommand(IcingaCommand $command)
    {
        $db = $this->db;

        $name = $command->getObjectName();
        if ($command::exists($name, $db)) {
            $new = $command::load($name, $db)
                ->replaceWith($command);
            if ($new->hasBeenModified()) {
                $new->store();

                return true;
            } else {
                return false;
            }
        } else {
            $command->store($db);

            return true;
        }
    }
    public function syncDatafields(IcingaCommand $command, $save=true){
        $db = $this->db;

        $name = $command->getObjectName();
        if ($command::exists($name, $db)) {
            $command = $command::load($name, $db);
            return $this->createDatafields($command,$save);

        } else {
            return $this->createDatafields($command,$save);
        }

    }

    /**
     * @return IcingaCommand
     */
    public function createCommand()
    {
        return IcingaCommand::create([
            'methods_execute' => 'PluginCheck',
            'object_name' => 'check_by_selenium',
            'object_type' => 'object',
            'command'     => '/usr/bin/icingacli selenium check',
            'arguments'   => $this->arguments(),
        ], $this->db());
    }

    public function getAllDatafields()
    {
        $datafields = [];
        $datafieldsFromDb = DirectorDatafield::loadAll($this->db());
        foreach ($datafieldsFromDb as $datafield) {
            $datafields[$datafield->get('varname')] = $datafield;
        }
        return $datafields;
    }
    public function createDatafields(IcingaCommand $command, $save)
    {
        $category_id = Config::module('selenium', "director")->getSection('datafield')->get('category_id');
        try {
            if(! DirectorDatafieldCategory::loadWithAutoIncId($category_id, $this->db())){
                $category_id=null;
            }
        }catch (\Throwable $e){
            $category_id=null;
        }

        $datafields=$this->getAllDatafields();
        $result=[];
        $outOfsync=false;
        foreach ($this->arguments() as $argument=>$properties){
            $isBoolean = false;
            if(isset($properties->set_if)){
                $isBoolean=true;
                $name=$properties->set_if;
            }else{
                $name=$properties->value;
            }
            $name = trim($name, '$');
            if(! in_array($name, array_keys($datafields))){ //datafield does not exist
                $result[$name]=['status'=>'new'];
                $field = DirectorDatafield::create([
                    'varname'     => $name,
                    'category_id' => $category_id,
                    'caption'     => $name,
                    'description' => $properties->description,
                    'datatype'    => $isBoolean
                        ? DataTypeBoolean::class
                        : DataTypeString::class
                ]);
                if($save) {
                    $field->store($this->db());
                }

                $addFieldToCommand = IcingaCommandField::create([
                    'command_id'   => $command->getAutoincId(),
                    'datafield_id' => $field->getAutoincId(),
                    'is_required'  => 'n',
                    'var_filter'   => null,
                ]);
                if($save){
                    $addFieldToCommand->store($this->db());
                }
                $outOfsync=true;

            }else{
                //datafield must exists by now
                $field = DirectorDatafield::create([
                    'varname'     => $name,
                    'caption'     => $name,
                    'category_id' => $category_id,
                    'description' => $properties->description,
                    'datatype'    => $isBoolean
                        ? DataTypeBoolean::class
                        : DataTypeString::class
                ]);
                /** @var $oldField DirectorDatafield */

                $oldField = $datafields[$name];

                foreach ($field->getProperties() as $key=>$value){
                    if(in_array($key,['id', 'uuid'])){
                        continue;
                    }
                    $oldField->set($key,$value);
                }


                if($oldField->hasBeenModified()){
                    $outOfsync=true;

                    $result[$name]=['status'=>'changed'];
                    if($save){
                        $oldField->store($this->db());
                    }
                }else{
                    $result[$name]=['status'=>'ok'];
                }

                $loader = new IcingaObjectFieldLoader($command);

                if(! in_array($name, array_keys($loader->getFields()))){
                    $result[$name]=['status'=>'not connected'];

                    $addFieldToCommand = IcingaCommandField::create([
                        'command_id'   => $command->getAutoincId(),
                        'datafield_id' => $oldField->getAutoincId(),
                        'is_required'  => 'n',
                        'var_filter'   => null,
                    ]);
                    if($save){
                        $addFieldToCommand->store($this->db());
                    }
                    $outOfsync=true;

                }
            }

        }

        return ['outOfSync'=>$outOfsync, 'fields'=>$result];

    }
    protected function arguments()
    {
        return [
            '--project'   => (object) [
                'value'       => '$selenium_project$',
                'required'    => true,
                'description' => 'Selenium Project Name',
            ],
            '--testsuite' => (object) [
                'value'       => '$selenium_testsuite$',
                'description' => 'The name of the Selenium testsuite to run',
                'required'    => false,
            ],
            '--test-id' => (object) [
                'value'       => '$selenium_test_id$',
                'description' => 'The test id in your Selenium config, set this to one id, if you only want to run one test',
                'required'    => false,
            ],
            '--suite-id' => (object) [
                'value'       => '$selenium_suite_id$',
                'description' => 'The suite id in your Selenium config, set this to one id, if you only want to run all tests of one suite',
                'required' => false,
            ],
            '--host' => (object) [
                'value'       => '$selenium_host$',
                'description' => 'The name of the host that will be applied to the Selenium config in case macros are used',
            ],
            '--service' => (object) [
                'value'       => '$selenium_service$',
                'description' => 'The name of the service that will be applied to the Selenium config in case macros are used',
            ],
            '--minimal' => (object) [
                'set_if'       => '$selenium_minimal$',
                'description' => 'Run the check in minimal mode, so less data will be written to the database.',
            ],
            '--with-images' => (object) [
                'set_if'       => '$selenium_with_images$',
                'description' => 'By default the cli command does not save images during execution, you can enable images here',
            ],
            '--remove-images-on-success' => (object) [
                'set_if'       => '$selenium_remove_images_on_success$',
                'description' => 'This parameter removes images from disk if the execution was successful',
            ],
            '--run-reference' => (object) [
                'value'       => '$selenium_run_reference$',
                'description' => 'You can set a run-reference which later allows you to query that particular test/suite/suites execution',
            ],
            '--critical' => (object) [
                'value'       => '$selenium_critical$',
                'description' => 'A Icinga critical threshold for the check execution in seconds.',
            ],
            '--warning' => (object) [
                'value'       => '$selenium_warning$',
                'description' => 'A Icinga warning threshold for the check execution in seconds.',
            ],
        ];
    }



    public function db()
    {
        if ($this->db === null) {
            $this->db = $this->initializeDb();
        }

        return $this->db;
    }

    protected function initializeDb()
    {
        $resourceName = Config::module('director')->get('db', 'resource');
        return Db::fromResourceName($resourceName);
    }
}
