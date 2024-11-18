<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Selenium\Model;

use Icinga\Application\Logger;
use Icinga\Application\Modules\Module;
use ipl\Html\Html;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Sql\Connection;

/**
 * A database model for Testrun with the testrun table
 *
 */
class Testrun extends DbModel
{
    public function getTableName(): string
    {
        return 'testrun';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumnDefinitions(): array
    {
        return [
            'name'=>[
                'fieldtype'=>'text',
                'label'=>'Name',
                'description'=>t('A Name of something'),
                'required'=>true
            ],

            'status'=>[
                'fieldtype'=>'text',
                'label'=>'status',
                'description'=>t('status'),
                'required'=>true
            ],

            'result'=>[
                'fieldtype'=>'textarea',
                'label'=>'Result',
                'description'=>t('Result'),
                'rows'=>20,
                'required'=>true
            ],
            'run_ref'=>[
                'fieldtype'=>'text',
                'label'=>'Run Reference',
                'description'=>t('Run Reference'),
                'required'=>false
            ],
            'ctime'=>[
                'fieldtype'=>t('localDateTime'),
                'label'=>t('Created At'),
                'description'=>t('A Creation Time'),
                'required'=>false
            ],
            'mtime'=>[
                'fieldtype'=>t('localDateTime'),
                'label'=>t('Created At'),
                'description'=>t('A Creation Time'),
                'required'=>false
            ],
            'testsuite_id'=>[
                'fieldtype'=>'select',
                'label'=>t('Testsuite'),
                'multiOptions'=>Testsuite::getAsArray('id','name'),
                'description'=>t('Testsuite'),
                'required'=>true
            ],
            'project_id'=>[
                'fieldtype'=>'select',
                'label'=>t('Project'),
                'multiOptions'=>Project::getAsArray('id','name'),
                'description'=>t('Project'),
                'required'=>true
            ]

        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        foreach ($this->getBooleans() as $name=>$properties){
            $behaviors->add(new BoolCast([$name]));
        }
        foreach ($this->getTimestamps() as $name=>$properties){
            $behaviors->add(new MillisecondTimestamp([$name]));
        }
    }
    public function getImages(){
        $result = [];
        $imagepath=Module::get('selenium')->getConfig('config')->getSection('images')->get('path',Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."images");
        $data = json_decode($this->result,true);
        if(isset($data['tests'])){
            foreach ($data['tests'] as $test){
                if($test['planned']){
                    foreach ($test['commands'] as $command){
                        if(isset($command['img'])){
                            if(!file_exists($command['img'])){
                                continue;
                            }
                            if(strpos(realpath($command['img']), $imagepath) !== false){
                                $result[] = $command['img'];
                            }else{
                                Logger::error("File can not be accessed, path not allowed : ".$command['img']);
                            }
                        }

                    }
                }
            }
        }
        return $result;
    }
    public function beforeDelete(Connection $db)
    {
        parent::beforeDelete($db);
        foreach ($this->getImages() as $image){
            unlink($image);
            Logger::info("File deleted successfully: ".$image);

        }

    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('testsuite', Testsuite::class)->setForeignKey('id')->setCandidateKey('testsuite_id');
        $relations->belongsTo('project', Project::class)->setForeignKey('id')->setCandidateKey('project_id');

    }

}
