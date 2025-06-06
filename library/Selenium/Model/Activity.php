<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Selenium\Model;

use Icinga\Application\Logger;

use Icinga\Web\Notification;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;


/**
 * A database model for Testsuite with the testsuite table
 *
 */
class Activity extends DbModel
{
    public function getTableName(): string
    {
        return 'activity';
    }

    public function getKeyName()
    {
        return 'id';
    }


    public function getColumnDefinitions(): array
    {
        return [
            'user'=>[
                'fieldtype'=>'text',
                'label'=>'User',
                'description'=>t('A Name of the user'),
                'required'=>true
            ],
            'model'=>[
                'fieldtype'=>'text',
                'label'=>'Modeltype',
                'description'=>t('Modeltype'),
                'required'=>true
            ],
            'model_id'=>[
                'fieldtype'=>'text',
                'label'=>'Model',
                'description'=>t('Modeltype'),
                'required'=>true
            ],
            'action'=>[
                'fieldtype'=>'select',
                'label'=>'Action',
                'multiOptions'=>['create','update','delete'],
                'description'=>t('Action'),
                'required'=>true
            ],
            'old'=>[
                'fieldtype'=>'textarea',
                'label'=>'old Data',
                'description'=>t('old Data'),
                'rows'=>20,
                'required'=>true,

            ],
            'new'=>[
                'fieldtype'=>'textarea',
                'label'=>'new Data',
                'description'=>t('old Data'),
                'rows'=>20,
                'required'=>true,

            ],
            'ctime'=>[
                'fieldtype'=>t('localDateTime'),
                'label'=>t('Created At'),
                'description'=>t('A Creation Time'),
                'required'=>false
            ],
            'mtime'=>[
                'fieldtype'=>t('localDateTime'),
                'label'=>t('Modified At'),
                'description'=>t('A Creation Time'),
                'required'=>false
            ],

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


    public function createRelations(Relations $relations)
    {

    }
    public function getOtherModel()
    {
        if($this->model == 'project'){
            $model = Project::findbyPrimaryKey($this->model_id);
        }elseif ($this->model == 'testsuite'){
            $model = Testsuite::findbyPrimaryKey($this->model_id);
        }else{
            $model= (object) ['name'=>'(not implemented)'];
        }
        if($model == null){
            if($this->action = "delete"){
                $data = json_decode($this->old);
            }else{
                $data = json_decode($this->new);
            }
            if(isset($data->name)){
                $model = (object) ['name'=>$data->name];
            }else{
                $model= (object) ['name'=>'(not implemented)'];
            }

        }
        return $model;
    }
    public function restoreModel(){
        $activity = $this;
        if($activity->action =="create"){
            Notification::error("Can't undo create, please use the delete button");
        }
        if ($activity->model === "project"){
            $data = json_decode($activity->old);
            if($activity->action =="update" || $activity->action =="delete"){
                $project = new Project();
                $project->id = $activity->model_id;
                $project->ctime = $data->ctime;
                $project->name = $data->name;
                $project->enabled = $data->enabled;
                $project->mtime = new \DateTime();
                $project->restore();
            }
        }elseif ($activity->model === "testsuite"){
            $data = json_decode($activity->old);
            if($activity->action =="update" || $activity->action =="delete"){
                $testsuite = new Testsuite();
                $testsuite->id = $activity->model_id;
                $testsuite->ctime = $data->ctime;
                $testsuite->name = $data->name;
                $testsuite->enabled = $data->enabled;
                $testsuite->generic = $data->generic;
                $testsuite->mtime = new \DateTime();
                $testsuite->data=json_encode($data->data,JSON_PRETTY_PRINT);
                $testsuite->sleep = $data->sleep;
                $testsuite->proxy = $data->proxy;
                $testsuite->project_id =$data->project_id;
                $testsuite->reference_object =$data->reference_object;
                $testsuite->implicit_wait =$data->implicit_wait;

                $testsuite->restore();

            }
        }



    }
}
