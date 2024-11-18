<?php
// icingaweb2-module-businessprocess | (c) Icinga GmbH | GPLv2

namespace Icinga\Module\Selenium\Forms;

use gipfl\IcingaWeb2\Widget\Content;
use Icinga\Web\Form;
use Icinga\Web\Notification;

class FileUploadForm extends Form
{


    public function createElements(array $formData)
        {
        $this->setAttrib('enctype', 'multipart/form-data');

        $this->addElement('file', 'uploaded_file', array(
            'label'       => $this->translate('File'),
            'destination' => $this->path,
            'required'    => true,
        ));

        /** @var \Zend_Form_Element_File $el */
        $el = $this->getElement('uploaded_file');
        $el->setValueDisabled(true);

        $this->setSubmitLabel(
            $this->translate('Next')
        );
    }



    protected function processUploadedSource()
    {
        /** @var \Zend_Form_Element_File $el */
        $el = $this->getElement('uploaded_file');

        if ($el) {

            $tmpfile = $this->mypath.DIRECTORY_SEPARATOR.$el->getFileName();

            // TODO: race condition, try to do this without unlinking here

            //$el->addFilter('Rename', $tmpfile);
            if ($el->receive()) {
                $this->setRedirectUrl('selenium/file');

            } else {
                foreach ($el->file->getMessages() as $error) {
                    $this->addError($error);
                }
            }
        }

        return $this;
    }

    public function onSuccess()
    {
        $this->processUploadedSource();

        parent::onSuccess();
    }
}
