<?php

/* originally from Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2 */
/* generated by icingaweb2-module-scaffoldbuilder | GPLv2+ */

namespace Icinga\Module\Selenium\Controllers;

use Icinga\Exception\ConfigurationError;

use Icinga\Module\Selenium\ProjectRestrictor;
use Icinga\Module\Selenium\Common\Database;
use Icinga\Module\Selenium\Controller;
use Icinga\Module\Selenium\Model\Project;

use Icinga\Module\Selenium\ProjectTable;
use Icinga\Module\Selenium\Web\Control\SearchBar\ObjectSuggestions;


use ipl\Web\Control\LimitControl;
use ipl\Web\Control\SortControl;

use ipl\Web\Url;
use ipl\Web\Widget\ButtonLink;

class ProjectsController extends Controller
{

    public function init()
    {
        $this->assertPermission('selenium/project');
        parent::init();
    }
    public function indexAction()
    {


        if ($this->hasPermission('selenium/project/modify')) {
            $this->addControl(
                (new ButtonLink(
                    $this->translate('New Project'),
                    Url::fromPath('selenium/project/new'),
                    'plus'
                ))->openInModal()
            );
        }

        $this->addTitleTab($this->translate('Projects'));

        try {
            $conn = Database::get();
        } catch (ConfigurationError $_) {
            $this->render('missing-resource', null, true);
            return;
        }

        $models = Project::on($conn)
            ->with([])
            ->withColumns([]);


        $sortColumns = [
            'name' => $this->translate('Name'),

        ];
        $restrictor = new ProjectRestrictor();
        $restrictor->applyRestrictions($models);

        $limitControl = $this->createLimitControl();
        $paginator = $this->createPaginationControl($models);
        $sortControl = $this->createSortControl($models, $sortColumns);

        $searchBar = $this->createSearchBar($models, [
            $limitControl->getLimitParam(),
            $sortControl->getSortParam()
        ]);

        if ($searchBar->hasBeenSent() && ! $searchBar->isValid()) {
            if ($searchBar->hasBeenSubmitted()) {
                $filter = $this->getFilter();
            } else {
                $this->addControl($searchBar);
                $this->sendMultipartUpdate();

                return;
            }
        } else {
            $filter = $searchBar->getFilter();
        }

        $models->peekAhead($this->view->compact);

        $models->filter($filter);

        $this->addControl($paginator);
        $this->addControl($sortControl);
        $this->addControl($limitControl);
        $this->addControl($searchBar);

        $this->addContent((new ProjectTable())->setData($models));

        if (! $searchBar->hasBeenSubmitted() && $searchBar->hasBeenSent()) {
            $this->sendMultipartUpdate(); // Updates the browser search bar
        }
    }

    public function completeAction()
    {
        $this->getDocument()->add(
            (new ObjectSuggestions())
                ->setModel(Project::class)
                ->forRequest($this->getServerRequest())
        );
    }

    public function searchEditorAction()
    {
        $editor = $this->createSearchEditor(Project::on(Database::get()), [
            LimitControl::DEFAULT_LIMIT_PARAM,
            SortControl::DEFAULT_SORT_PARAM
        ]);

        $this->getDocument()->add($editor);
        $this->setTitle(t('Adjust Filter'));
    }


}
