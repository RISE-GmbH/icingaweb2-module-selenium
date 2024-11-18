<?php

namespace Icinga\Module\Selenium\Controllers;

use Icinga\Application\Logger;

use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Module\Selenium\Forms\FileUploadForm;
use Icinga\Web\UrlParams;
use ipl\Html\Html;
use ipl\Web\Compat\CompatController;

class FileController extends CompatController
{
    /** @var UrlParams */
    protected $params;


    public function init()
    {

    }
    public function uploadAction()
    {
        $this->createTabs()->activate("seleninum/file/upload");
        $title = $this->translate('Upload a file');
        $this->setTitle($title);
        $this->view->headline= Html::tag('h1', null, $title);
        $form = (new FileUploadForm());
        $form->handleRequest();

        $this->view->form= $form;
    }



    public function jsAction()
    {
        $user = $this->Auth()->getUser()->getUsername();
        $file = $this->params->shift('file');
        $path =$this->Module()->getJsDir().DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR."ace";
        $realPath=realpath($path.$file);
        if(strpos($realPath,$path) ===0){
            if (file_exists($realPath)) {
                ob_get_clean();
                header("Content-type: application/javascript");
                header('Pragma: public');
                ob_clean();
                flush();
                readfile($realPath);
                exit;
            }
        }else{
            Logger::error($user." tried path traversal in selenium/FileController");
            throw new HttpNotFoundException("File not found");
        }

    }

    public function saveAction()
    {

        $post = $this->getRequest()->getPost();
        if(isset($post['content']) &&isset($post['folder']) && isset($post['file']) && in_array($post['folder'],['mips', 'generated_files'])){

            $realPath = realpath($this->path.DIRECTORY_SEPARATOR.$post['folder'].DIRECTORY_SEPARATOR.$post['file']);
            if(strpos($realPath,$this->path) ===0){
                if (file_exists($realPath)) {
                    file_put_contents($realPath, base64_decode($post['content']));

                    echo json_encode(['success'=>true, 'content'=> file_get_contents($realPath)]);
                    exit(0);
                }
            }
        }

        echo json_encode(['success'=>false]);
        exit(1);
    }
    public function downloadAction()
    {


    }


}