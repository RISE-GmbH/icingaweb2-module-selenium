<?php

/* Originally from Icinga Web 2 Reporting Module (c) Icinga GmbH | GPLv2+ */
/* generated by icingaweb2-module-scaffoldbuilder | GPLv2+ */

namespace Icinga\Module\Selenium\Forms;

use Icinga\Application\Modules\Module;
use Icinga\Forms\ConfigForm;

class ModuleconfigForm extends ConfigForm
{

    public function init()
    {

        $this->setName('selenium_settings');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    public function createElements(array $formData)
    {

        $this->addElement('text', 'selenium_proxy', [
            'label' => $this->translate('Proxy'),
            'description' => $this->translate('A proxy to use for chrome headless "host:port"'),
        ]);

        $this->addElement('text', 'images_path', [
            'label' => $this->translate('Images path'),
            'description' => $this->translate('The path where to save the images for the selenium checks'),
            'value'=> Module::get('selenium')->getConfigDir().DIRECTORY_SEPARATOR."images"
        ]);

        $this->addElement('number', 'images_retentionpolicy', [
            'label' => $this->translate('Images retentionpolicy'),
            'description' => $this->translate('The duration in days to keep images'),
            'value'=> '30'

        ]);
        $this->addElement('number', 'testruns_retentionpolicy', [
            'label' => $this->translate('Testruns retentionpolicy'),
            'description' => $this->translate('The duration in days to keep testruns'),
            'value'=> '30'

        ]);

    }

}