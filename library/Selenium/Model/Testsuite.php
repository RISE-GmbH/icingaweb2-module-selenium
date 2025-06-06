<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Selenium\Model;

use Icinga\Authentication\Auth;
use Icinga\Module\Selenium\SeleniumConfigValidator;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;
use ipl\Stdlib\Filter;


/**
 * A database model for Testsuite with the testsuite table
 *
 */
class Testsuite extends DbModel
{
    public function getTableName(): string
    {
        return 'testsuite';
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
            'data'=>[
                'fieldtype'=>'textarea',
                'label'=>'Data',
                'description'=>t('Data'),
                'rows'=>20,
                'required'=>true,
                'validators' => [new SeleniumConfigValidator()],

            ],
            'enabled'=>[
                'fieldtype'=>'checkbox',
                'label'=>t('Enabled'),
                'description'=>t('Enable or disable this testsuite'),
                'required'=>false
            ],
            'generic'=>[
                'fieldtype'=>'checkbox',
                'label'=>t('Generic'),
                'description'=>t('This is a generic test that can be applied for different icinga objects'),
                'required'=>false
            ],
            'reference_object'=>[
                'fieldtype'=>'text',
                'label'=>'Reference Object',
                'description'=>t('A reference object if the test gets executed without a specific object applied'),
                'required'=>true
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
            'implicit_wait'=>[
                'fieldtype'=>'number',
                'label'=>t('Implicit Wait'),
                'description'=>t('The Chrome WebDriver option for implicit wait, helps selenium to decide if a page is ready or not, this influnces the assert element entries'),
                'required'=>false,
                'value'=>4,
                'step'=>'0.1'
            ],
            'sleep'=>[
                'fieldtype'=>'number',
                'label'=>t('Sleep'),
                'description'=>t('Sleep between command and screenshot'),
                'required'=>false,
                'value'=>1,
                'step'=>'0.1'
            ],
            'project_id'=>[
                'fieldtype'=>'select',
                'label'=>t('Project'),
                'multiOptions'=>Project::getAsArray('id','name'),
                'description'=>t('A Creation Time'),
                'required'=>true
            ],

            'proxy'=>[
                'fieldtype'=>'text',
                'label'=>'Proxy',
                'description'=>t('This proxy will be used for this testsuite, leave empty if no proxy shall be used'),
                'required'=>false,

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
        $relations->belongsTo('project', Project::class)->setForeignKey('id')->setCandidateKey('project_id');
        $relations->hasMany('testrun', Testrun::class)->setForeignKey('id')->setCandidateKey('testsuite_id');

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
        $activity->model = "testsuite";


        $activity->ctime = new \DateTime();

        if(isset($this->id)){
            $oldModel = Testsuite::on($db)->filter(Filter::equal('id',$this->id))->first();

            if($oldModel === null){
                $activity->action = "create";
                $old = [];
            }else{
                $activity->action = "update";
                $old = [];
                $oldProject = Project::on($db)->filter(Filter::equal('id',$oldModel->project_id))->first();
                $old['name']=$oldModel->name;
                $old['data']=json_decode($oldModel->data);
                $old['enabled']=$oldModel->enabled;
                $old['generic']=$oldModel->generic;
                $old['implicit_wait']=$oldModel->implicit_wait;
                $old['sleep']=$oldModel->sleep;
                $old['ctime']=$oldModel->ctime;
                $old['mtime']=$oldModel->mtime;
                $old['project']=$oldProject->name;
                $old['project_id']=sprintf("%s",$oldModel->project_id);
                $old['proxy']=$oldModel->proxy;
                $old['reference_object']=$oldModel->reference_object;
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
                if(is_bool($old['generic'])){
                    if($old['generic'] === true){
                        $old['generic']="y";
                    }else{
                        $old['generic']="n";
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
            $newProject = Project::on($db)->filter(Filter::equal('id',$this->project_id))->first();

            $new = [];
            $new['name']=$this->name;
            $new['data']=json_decode($this->data);
            $new['enabled']=$this->enabled;
            $new['generic']=$this->generic;
            $new['implicit_wait']=$this->implicit_wait;
            $new['sleep']=$this->sleep;
            $new['ctime']=$this->ctime;
            $new['mtime']=$this->mtime;
            $new['project']=$newProject->name;
            $new['project_id']=$this->project_id;
            $new['proxy']=$this->proxy;
            $new['reference_object']=$this->reference_object;
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
            if(is_bool($new['generic'])){
                if($new['generic'] === true){
                    $new['generic']="y";
                }else{
                    $new['generic']="n";
                }
            }
        }

        $activity->old = json_encode($old,JSON_PRETTY_PRINT);

        $activity->new = json_encode($new,JSON_PRETTY_PRINT);




        $activity->save(false);
        $this->lastActivity = $activity;
    }

}
