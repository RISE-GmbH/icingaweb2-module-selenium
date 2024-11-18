<?php

/* Originally from Icinga Web 2 Reporting Module (c) Icinga GmbH | GPLv2+ */
/* icingaweb2-module-scaffoldbuilder 2023 | GPLv2+ */

namespace Icinga\Module\Selenium\Controllers;


use Icinga\Application\Config;
use Icinga\Web\Controller;
use Icinga\Module\Selenium\Forms\ModuleconfigForm;

class ModuleconfigController extends Controller
{



    public function init()
    {
        $this->assertPermission('config/selenium');
        parent::init();
    }


    public function indexAction()
    {
        $form = (new ModuleconfigForm())
            ->setIniConfig(Config::module('selenium', "config"));

        $form->handleRequest();

        $this->view->tabs = $this->Module()->getConfigTabs()->activate('config/moduleconfig');
        $this->view->form = $form;
    }


    public function createTabs()
    {
        $tabs = $this->getTabs();

        $tabs->add('selenium/config', [
            'label' => $this->translate('Configure Selenium'),
            'url' => 'selenium/config'
        ]);

        return $tabs;

    }

}