<?php

/** @var \Icinga\Application\Modules\Module $this */

$section = $this->menuSection(N_('Selenium'), [
    'url'=>'selenium',
    'icon' => 'beaker',
    'priority' => 910
]);



$this->provideRestriction(
    'selenium/filter/projects',
    $this->translate('Restrict access to the Selenium projects and corresponding objects that match the filter')
);



?>
<?php

$this->provideConfigTab('config/moduleconfig', array(
    'title' => $this->translate('Module Configuration'),
    'label' => $this->translate('Module Configuration'),
    'url' => 'moduleconfig'
));


$this->provideConfigTab('config/director', array(
    'title' => $this->translate('Director Configuration'),
    'label' => $this->translate('Director Configuration'),
    'url' => 'director'
));

$this->providePermission('config/selenium', $this->translate('allow access to selenium configuration'));

?>
<?php

$this->provideConfigTab('backend', array(
    'title' => $this->translate('Configure the database backend'),
    'label' => $this->translate('Backend'),
    'url' => 'database/backend'
));

?>
<?php

$section->add(N_('Project'))
    ->setUrl('selenium/projects')
    ->setPermission('selenium/project')
    ->setPriority(30);
?>
<?php

$section->add(N_('Testsuite'))
    ->setUrl('selenium/testsuites')
    ->setPermission('selenium/testsuite')
    ->setPriority(30);
?>

<?php

$section->add(N_("Testrun"), array(
    'url' => 'selenium/testruns',
    'urlParameters' => ['sort'=>"id desc"],
    'priority' => 40,
    'permission' => 'selenium/testrun',
));
$this->providePermission('selenium/project', $this->translate('allow access to projects'));
$this->providePermission('selenium/testsuite', $this->translate('allow access to testsuites'));
$this->providePermission('selenium/testrun', $this->translate('allow access to testruns'));

$this->providePermission('selenium/project/modify', $this->translate('allow access to modify projects'));
$this->providePermission('selenium/testsuite/modify', $this->translate('allow access to modify testsuites'));
$this->providePermission('selenium/testrun/modify', $this->translate('allow access to testruns'));


$this->providePermission('selenium/service/restart', $this->translate('allow to restart selenium service'));
$this->providePermission('selenium/driver/init', $this->translate('allow to init/update the webdriver'));


?>