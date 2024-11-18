<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Selenium\Controllers;

use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\QueryException;

use Icinga\Module\Monitoring\Backend\MonitoringBackend;

use Icinga\Web\Widget\SingleValueSearchControl;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Compat\CompatController;


class SuggestObjectsIdoController extends CompatController
{

    protected function getBackend()
    {
        MonitoringBackend::clearInstances();

        return MonitoringBackend::instance();
    }





    protected function yieldMonitoringRestrictions()
    {
        foreach ($this->getRestrictions('monitoring/filter/objects') as $restriction) {
            if ($restriction !== '*') {
                yield Filter::fromQueryString($restriction);
            }
        }
    }

    protected function applyFilterAndRestrictions($filter, Filterable $filterable)
    {
        $filters = Filter::matchAll();
        $filters->setAllowedFilterColumns(array(
            'host_name',
            'host_display_name',
            'service_description',
            'service_display_name',
            function ($c) {
                return \preg_match('/^_(?:host|service)_/i', $c);
            }
        ));

        try {
            if ($filter !== '*') {
                $filters->addFilter(Filter::fromQueryString($filter));
            }

            foreach ($this->yieldMonitoringRestrictions() as $filter) {
                $filters->addFilter($filter);
            }
        } catch (QueryException $e) {
            throw new ConfigurationError(
                'Cannot apply filter. You can only use the following columns: %s',
                implode(', ', array(
                    'host_display_name',
                    'host_name',
                    'service_description',
                    'service_display_name',
                    '_(host|service)_<customvar-name>'
                )),
                $e
            );
        }

        $filterable->applyFilter($filters);
    }
    public function indexAction()
    {
        $this->assertHttpMethod('POST');
        $requestData = $this->getRequest()->getPost();
        $limit = $this->params->get('limit', 50);

        $searchTerm = $requestData['term']['label'];
        $hostFilter = "(host_display_name=$searchTerm|host_name=$searchTerm)";
        $serviceFilter = "(service_display_name=$searchTerm|service_description=$searchTerm|host_display_name=$searchTerm|host_name=$searchTerm)";

        $suggestions = [];

        if ($limit > 0 ) {
            $hostQuery = $this
                ->getBackend()
                ->select()
                ->from('hoststatus', ['host_name'])
                ->order('host_name')->limit($limit);

            $this->applyFilterAndRestrictions($hostFilter ?: '*', $hostQuery);

            /** @var \Zend_Db_Select $select */
            $select = $hostQuery->getQuery()->getSelectQuery();

            $columns = $hostQuery->getQuery()->getColumns();
            $columns['object_id'] = new \Zend_Db_Expr(
                'ho.object_id');
            $select->columns($columns);

            $allHosts= $this->getBackend()->getResource()->getDbAdapter()->query($select);


            $serviceQuery = $this
                ->getBackend()
                ->select()
                ->from('servicestatus', ['host_name', 'service_description'])
                ->order('service_description')->limit($limit);

            $this->applyFilterAndRestrictions($serviceFilter ?: '*', $serviceQuery);

            /** @var \Zend_Db_Select $select */
            $select = $serviceQuery->getQuery()->getSelectQuery();

            $columns = $serviceQuery->getQuery()->getColumns();

            $columns = [new \Zend_Db_Expr(\sprintf(
                "%s",
                'so.object_id'

            )),new \Zend_Db_Expr(\sprintf(
                "%s",
                'h.host_object_id as host_object_id'

            ))];

            $select->columns($columns);

            $allServices= $this->getBackend()->getResource()->getDbAdapter()->query($select);

            $domain="";
            $hosts = [];
            $services = [];

            foreach ($allHosts as $row) {

                $hosts[] = [$row->host_name , [$row->host_name]];
            }

            foreach ($allServices as $row) {
                $services[] = [$row->service_description , [$row->host_name."!".$row->service_description ]];
            }


            if (! empty($hosts)) {
                $suggestions[] = [
                    [
                        t('Hosts'),
                        HtmlString::create('&nbsp;'),
                        Html::tag('span', ['class' => 'badge'], "IDO")
                    ],
                    $hosts
                ];
            }
            if (! empty($services)) {
                $suggestions[] = [
                    [
                        t('Services'),
                        HtmlString::create('&nbsp;'),
                        Html::tag('span', ['class' => 'badge'], "IDO")
                    ],
                    $services
                ];
            }

        }



        if (empty($suggestions)) {
            $suggestions[] = [t('Your search does not match any object'), []];
        }

        $this->document->add(SingleValueSearchControl::createSuggestions($suggestions));
    }
}
