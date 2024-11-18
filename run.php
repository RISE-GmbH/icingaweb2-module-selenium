<?php
/** @var $this \Icinga\Application\Modules\Module */

use Icinga\Application\Modules\Module;

$this->provideHook('DbMigration', '\\Icinga\\Module\\Selenium\\ProvidedHook\\DbMigration');

if (Module::exists('icingadb')  ) {
    $this->provideHook('icingadb/ServiceActions');
}
if (Module::exists('monitoring')  ) {
    $this->provideHook('monitoring/ServiceActions');
}

require_once 'vendor/autoload.php';


$this->provideHook('health', 'SeleniumDriverHealth');
$this->provideHook('health', 'SeleniumServiceHealth');

$object_backend = \Icinga\Application\Config::module('selenium')->getSection('backend')->get('object');
if($object_backend === "monitoring"){
    if (Module::exists('monitoring')  ) {

        $this->addRoute('selenium/suggest-object', new Zend_Controller_Router_Route_Static(
            'selenium/suggest-object',
            [
                'controller'    => 'suggest-object-ido',
                'action'        => 'index',
                'module'        => 'selenium'
            ]
        ));


    }else{
        \Icinga\Application\Logger::error("selenium: monitoring backend chosen but not enabled...");
    };
}

