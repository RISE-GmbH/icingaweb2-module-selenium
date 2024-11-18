<?php

/* originally from Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2 */

/* generated by icingaweb2-module-scaffoldbuilder | GPLv2+ */

namespace Icinga\Module\Selenium;

use Icinga\Authentication\Auth;
use Icinga\Module\Selenium\Model\Testsuite;
use Icinga\Web\Url;
use ipl\Html\Html;
use ipl\Orm\Model;
use ipl\Web\Widget\Icon;

/**
 * Table widget to display a list of Testsuites
 */
class TestsuiteTable extends DataTable
{
    protected $defaultAttributes = [
        'class' => 'usage-table common-table table-row-selectable',
        'data-base-target' => '_next'
    ];

    public function createColumns()
    {
        $columns = [];
        foreach ((new Testsuite())->getColumnDefinitions() as $column => $options) {
            if (is_array($options)) {
                $fieldtype = $options['fieldtype'] ?? "text";

                unset($options['fieldtype']);

                if ($column === "project_id") {
                    $columns[$column . "_txt"] = [
                        'label' => $options['label'] ?? $column,
                        'column' => function ($data) {
                            return $data;
                        },
                        'renderer' => function ($data) {
                            if ($data->project !== null) {
                                return $data->project->name;
                            }
                            return t("not set");
                        }
                    ];
                } elseif ($fieldtype === "autocomplete" || $fieldtype === "text" || $fieldtype === "select") {
                    $columns[$column] = $options['label'] ?? $column;

                } elseif ($column === "enabled") {

                    $columns[$column . "_text"] = [
                        'label' => $options['label'] ?? $column,
                        'column' => function ($data) {
                            return $data->enabled ? t("Yes") : t("No");
                        }
                    ];
                } elseif ($column === "generic") {

                    $columns[$column . "_text"] = [
                        'label' => $options['label'] ?? $column,
                        'column' => function ($data) {
                            return $data->generic ? t("Yes") : t("No");
                        }
                    ];
                } elseif ($column === "ctime") {
                    $columns[$column . "_txt"] = [
                        'label' => $options['label'] ?? $column,
                        'column' => function ($data) {
                            if ($data->ctime != null) {
                                return $data->ctime->format("c");
                            }
                            return t("not set");
                        }
                    ];
                } elseif ($column === "mtime") {
                    $columns[$column . "_txt"] = [
                        'label' => $options['label'] ?? $column,
                        'column' => function ($data) {
                            if ($data->mtime != null) {
                                return $data->mtime->format("c");
                            }
                            return t("not set");
                        }
                    ];
                }
            } else {
                $columns[$column] = $options;
            }
        }

        $columns["runall"] = [

            'label' => mt('selenium', 'Run All'),
            'attributes' => ['class' => 'icon-col'],
            'column' => function ($data) {
                return $data;
            },
            'renderer' => function ($data) {
                $div = Html::tag("div", ['style' => 'white-space: nowrap;']);

                $icon = new Icon('flask-vial', ['title' => mt('selenium', 'run all suites')]);
                $a = Html::tag("a", ['target' => '_next', 'style' => 'padding-right:1em; display:inline;', 'href' => Url::fromPath('selenium/testsuite/run', ['id' => $data->id])]);
                $a->add($icon);
                $div->add($a);
                return $div;

            }
        ];
        $columns["suits"] = [

            'label' => mt('selenium', 'Suits'),
            'attributes' => ['class' => 'icon-col'],
            'column' => function ($data) {
                return $data;
            },
            'renderer' => function ($data) {
                $div = Html::tag("div", ['style' => 'white-space: nowrap;']);

                $content = json_decode($data->data, true);
                foreach ($content['suites'] as $suite) {
                    $icon = new Icon('flask', ['title' => mt('selenium', 'run all tests from suite') . " " . $suite['name']]);
                    $a = Html::tag("a", ['target' => '_next', 'style' => 'padding-right:1em; display:inline;', 'href' => Url::fromPath('selenium/testsuite/run', ['id' => $data->id, 'suite-ref' => $suite['id']])]);
                    $a->add($icon);
                    $div->add($a);
                    $div->add(Html::tag("br"));
                }
                return $div;

            }
        ];

        $columns["tests"] = [

            'label' => mt('selenium', 'Tests'),
            'attributes' => ['class' => 'icon-col'],
            'column' => function ($data) {
                return $data;
            },
            'renderer' => function ($data) {
                $div = Html::tag("div", ['style' => 'white-space: nowrap;']);

                $content = json_decode($data->data, true);
                foreach ($content['suites'] as $suite) {
                    foreach ($suite['tests'] as $test_ref) {
                        foreach ($content['tests'] as $test) {
                            if ($test['id'] === $test_ref) {
                                $icon = new Icon('vial-virus', ['title' => mt('selenium', 'run test') . " " . $test['name'] . " " . mt('selenium', 'from suite') . " " . $suite['name']]);
                                $a = Html::tag("a", ['target' => '_next', 'style' => 'padding-right:1em; display:inline;', 'href' => Url::fromPath('selenium/testsuite/run', ['id' => $data->id, 'suite-ref' => $suite['id'], 'test-ref' => $test['id']])]);
                                $a->add($icon);
                                $div->add($a);
                            }
                        }

                    }


                    $div->add(Html::tag("br"));

                }
                return $div;

            }
        ];
        return $columns;
    }

    protected function renderRow(Model $row)
    {
        $tr = parent::renderRow($row);

        if (Auth::getInstance()->hasPermission('selenium/testsuite/modify')) {
            $url = Url::fromPath('selenium/testsuite/edit', ['id' => $row->id]);

            $tr->getFirst("td")->getAttributes()->add(['href' => $url->getAbsoluteUrl(), 'data-icinga-modal' => true,
                'data-no-icinga-ajax' => true]);

        }

        return $tr;
    }
}
