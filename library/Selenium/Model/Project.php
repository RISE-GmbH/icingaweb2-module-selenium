<?php

/* Icinga Web 2 X.509 Module | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Selenium\Model;

use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

/**
 * A database model for Project with the project table
 *
 */
class Project extends DbModel
{
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

}
