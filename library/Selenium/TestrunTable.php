<?php

/* originally from Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2 */
/* generated by icingaweb2-module-scaffoldbuilder | GPLv2+ */

namespace Icinga\Module\Selenium;

use DateTime;
use Icinga\Authentication\Auth;
use Icinga\Module\Selenium\Model\Testrun;
use Icinga\Web\Url;
use ipl\Html\Html;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;

/**
 * Table widget to display a list of Testruns
 */
class TestrunTable extends DataTable
{
    protected $defaultAttributes = [
        'class'            => 'usage-table common-table table-row-selectable',
        'data-base-target' => '_next'
    ];

    public function createColumns()
    {
        $columns =[];
        foreach ((new Testrun())->getColumnDefinitions() as $column => $options){
            if(is_array($options)) {
                $fieldtype = $options['fieldtype'] ?? "text";

                unset($options['fieldtype']);

                if ($column === "status"){
                    $columns[$column."_txt"] = [
                        'label'  => $options['label']??$column,
                        'column' => function ($data) {
                            return $data;
                        },
                        'renderer' => function ($data) {
                            $currentDateTime = new DateTime();



                            if ($data->status === "running" && $currentDateTime->getTimestamp() - $data->ctime->getTimestamp() >= 600) {
                                $data->status = "failed";
                            }
                            if($data->status === "success"){
                                return Html::tag("a",['style'=>'color:green'],$data->status );
                            }elseif ($data->status === "running"){
                                return Html::tag("a",['style'=>'color:blue'],$data->status );

                            }elseif ($data->status === "failed"){
                                return Html::tag("a",['style'=>'color:red'],$data->status );

                            }
                            return t("not set");
                        }
                    ];
                }elseif ($column === "project_id"){
                    $columns[$column."_txt"] = [
                        'label'  => $options['label']??$column,
                        'column' => function ($data) {
                            return $data;
                        },
                        'renderer' => function ($data) {
                            if($data->project !== null){
                                return $data->project->name;
                            }
                            return t("not set");
                        }
                    ];
                }elseif ($column === "testsuite_id"){
                    $columns[$column."_txt"] = [
                        'label'  => $options['label']??$column,
                        'column' => function ($data) {
                            return $data;
                        },
                        'renderer' => function ($data) {
                            if($data->testsuite !== null){
                                return $data->testsuite->name;
                            }
                            return t("not set");
                        }
                    ];
                }elseif ($fieldtype === "autocomplete" || $fieldtype === "text" || $fieldtype === "select") {
                    $columns[$column] = $options['label']??$column;

                }elseif ($column === "ctime"){
                    $columns[$column."_txt"] = [
                        'label'  => $options['label']??$column,
                        'column' => function ($data) {
                            if($data->ctime!=null){
                                return $data->ctime->format("c");
                            }
                            return t("not set");
                        }
                    ];
                }elseif ($column === "mtime"){
                    $columns[$column."_txt"] = [
                        'label'  => $options['label']??$column,
                        'column' => function ($data) {
                            if($data->mtime!=null){
                                return $data->mtime->format("c");
                            }
                            return t("not set");
                        }
                    ];
                }
            }else{
                $columns[$column] = $options;
            }
        }
        $columns["view"] = [

            'label' => mt('selenium', 'Action'),
            'attributes' => ['class' => 'icon-col'],
            'column' => function ($data) {
                return $data;
            },
            'renderer' => function ($data) {
                $div=Html::tag("div",['style'=>'white-space: nowrap;']);
                $icon=  new Icon('eye', ['title' => mt('selenium', 'view')]);
                $a = Html::tag("a",['target'=>'_next', 'style'=>'padding-right:1em; display:inline;','href'=>Url::fromPath('selenium/testrun/view',['id'=>$data->id])]);
                $a->add($icon);
                $div->add($a);
                $div->add(Html::tag("br"));

                return $div;

            }
        ];

        return $columns;
    }


    protected function renderRow(Model $row)
    {
        $tr = parent::renderRow($row);

        if (Auth::getInstance()->hasPermission('selenium/testrun/modify')) {
            $url = Url::fromPath('selenium/testrun/edit', ['id' => $row->id]);

            $tr->getFirst("td")->getAttributes()->add(['href' => $url->getAbsoluteUrl(), 'data-icinga-modal' => true,
                'data-no-icinga-ajax' => true]);

        }

        return $tr;
    }
}