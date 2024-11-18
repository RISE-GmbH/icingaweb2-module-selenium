<?php

/* originally from Icinga Web 2 X.509 Module | (c) 2018 Icinga GmbH | GPLv2 */
/* Icingadbreports | (c) Nicolas Schneider 2023 Rise GmbH | GPLv2 */

namespace Icinga\Module\Selenium\Controllers;

use Icinga\Module\Icingadb\Model\Host;
use Icinga\Module\Icingadb\Model\Service;

use Icinga\Module\Selenium\Common\IcingaDbDatabase;
use Icinga\Web\Widget\SingleValueSearchControl;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Filter\QueryString;


class SuggestObjectController extends \Icinga\Module\Icingadb\Web\Controller
{

    public function indexAction()
    {
        $db = IcingaDbDatabase::get();
        $this->assertHttpMethod('POST');
        $requestData = $this->getRequest()->getPost();
        $limit = $this->params->get('limit', 50);

        $searchTerm = $requestData['term']['label'];


        $suggestions = [];

        if ($limit > 0) {

            $hostsFromDb = Host::on($db)
                ->columns([
                    'name',
                ]);

            $this->applyRestrictions($hostsFromDb);
            $filter1 = "host.name~$searchTerm";
            $filter1 = QueryString::parse($filter1);
            $filter2 = "host.display_name~$searchTerm";
            $filter2 = QueryString::parse($filter2);
            $hostsFromDb->orFilter($filter1);
            $hostsFromDb->orFilter($filter2);

            $hosts = [];

            foreach ($hostsFromDb as $row) {

                $hosts[] = [$row->name,[$row->name]];
            }

            if (!empty($hosts)) {
                $suggestions[] = [
                    [
                        t('Hosts'),
                        HtmlString::create('&nbsp;'),
                        Html::tag('span', ['class' => 'badge'], "IcingaDB")
                    ],
                    $hosts
                ];
            }


            $servicesFromDb = Service::on($db)->with("host")
                ->columns([
                    'service.name',
                    'service.id',
                    'host.name',
                    'host.id',
                ]);

            $this->applyRestrictions($servicesFromDb);
            $filter1 = "name~$searchTerm";
            $filter1 = QueryString::parse($filter1);
            $filter2 = "display_name~$searchTerm";
            $filter2 = QueryString::parse($filter2);
            $filter3 = "host.name~$searchTerm";
            $filter3 = QueryString::parse($filter3);
            $filter4 = "host.display_name~$searchTerm";
            $filter4 = QueryString::parse($filter4);
            $servicesFromDb->orFilter($filter1);
            $servicesFromDb->orFilter($filter2);
            $servicesFromDb->orFilter($filter3);
            $servicesFromDb->orFilter($filter4);

            $services = [];

            foreach ($servicesFromDb as $row) {

                $services[] = [$row->host->name . "!" . $row->name ,[$row->host->name . "!" . $row->name]];
            }

            if (!empty($services)) {
                $suggestions[] = [
                    [
                        t('Services'),
                        HtmlString::create('&nbsp;'),
                        Html::tag('span', ['class' => 'badge'], "IcingaDB")
                    ],
                    $services
                ];
            }
        }


        if (empty($suggestions)) {
            $suggestions[] = [t('Your search does not match any hosts'), []];
        }

        $this->document->add(SingleValueSearchControl::createSuggestions($suggestions));
    }
}
