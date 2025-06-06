<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Selenium\Model;

use Icinga\Authentication\Auth;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Stdlib\Filter;

/**
 * A database model for Project with the project table
 *
 */
class Project extends DbModel
{
    protected $lastActivity;
    public function getTableName(): string
    {
        return 'project';
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
            'enabled'=>[
                'fieldtype'=>'checkbox',
                'label'=>t('Enabled'),
                'description'=>t('Enable or disable something'),
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
        $relations->hasMany('testsuite', Testsuite::class)->setForeignKey('project_id')->setCandidateKey('id');
        $relations->hasMany('testrun', Testrun::class)->setForeignKey('project_id')->setCandidateKey('id');
    }

    public function beforeSave($db)
    {
        parent::beforeSave($db);
        $this->generateActivity($db, 'modify');


    }

    public function afterSave($db)
    {
        parent::afterSave($db);

        $this->lastActivity->model_id =$this->id;
        $this->lastActivity->save(false);
    }

    public function beforeDelete($db)
    {
        parent::beforeDelete($db);
        $this->generateActivity($db, 'delete');
    }

    public function generateActivity($db,$mode="modify")
    {
        $activity = new Activity();
        $activity->user = Auth::getInstance()->getUser()->getUsername();
        $activity->model = "project";


        $activity->ctime = new \DateTime();

        if(isset($this->id)){
            $oldModel = Project::on($db)->filter(Filter::equal('id',$this->id))->first();
            $old = [];

            if($oldModel === null){
                $activity->action = "create";
                $old = [];
            }else{
                $activity->action = "update";
                $old['name']=$oldModel->name;
                $old['enabled']=$oldModel->enabled;
                $old['ctime']=$oldModel->ctime;
                $old['mtime']=$oldModel->mtime;
                if($old['ctime'] instanceof \DateTime){
                    $old['ctime']=$old['ctime']->format('Uv') ;
                }
                if($old['mtime'] instanceof \DateTime){
                    $old['mtime']=$old['mtime']->format('Uv') ;
                }

                if(is_bool($old['enabled'])){
                    if($old['enabled'] === true){
                        $old['enabled']="y";
                    }else{
                        $old['enabled']="n";
                    }
                }
            }

            $activity->model_id = $this->id;

            if($mode == "delete"){
                $new = [];
                $activity->action = "delete";
            }

        }else{
            $old=[];

            $activity->action = "create";


        }

        if($mode == "modify"){
            $new = [];
            $new['name']=$this->name;
            $new['enabled']=$this->enabled;
            $new['ctime']=$this->ctime;
            $new['mtime']=$this->mtime;
            if($new['ctime'] instanceof \DateTime){
                $new['ctime']=$new['ctime']->format('Uv') ;
            }
            if($new['mtime'] instanceof \DateTime){
                $new['mtime']=$new['mtime']->format('Uv') ;
            }
            if(is_bool($new['enabled'])){
                if($new['enabled'] === true){
                    $new['enabled']="y";
                }else{
                    $new['enabled']="n";
                }
            }
        }

        $activity->old = json_encode($old,JSON_PRETTY_PRINT);

        $activity->new = json_encode($new,JSON_PRETTY_PRINT);




        $activity->save(false);
        $this->lastActivity = $activity;
    }

}
