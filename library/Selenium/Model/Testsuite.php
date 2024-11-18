<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Selenium\Model;

use Icinga\Module\Selenium\SeleniumConfigValidator;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;


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
                'description'=>t('Enable or disable something'),
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


    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('project', Project::class)->setForeignKey('id')->setCandidateKey('project_id');
        $relations->hasMany('testrun', Testrun::class)->setForeignKey('id')->setCandidateKey('testsuite_id');

    }
}
