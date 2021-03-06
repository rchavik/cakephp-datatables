<?php

namespace DataTables\Controller;

use Cake\Controller\Controller;
use Cake\Error\FatalErrorException;
use Cake\Http\ServerRequest;
use \Cake\Utility\Inflector;
use Cake\View\ViewBuilder;

/**
 * CakePHP DataTablesComponent
 *
 * @property \DataTables\Controller\Component\DataTablesComponent $DataTables
 * @property ServerRequest|null request
 * @property Controller $this
 * @method ViewBuilder viewBuilder()
 * @author allan
 */
trait DataTablesAjaxRequestTrait
{

    /**
     * @var callable
     */
    private $dataTableBeforeAjaxFunction = null;

    /**
     * @var callable
     */
    private $dataTableAfterAjaxFunction = null;

    /**
     * Set a function to be exec before ajax request
     * @param callable $dataTableBeforeAjaxFunction
     */
    public function setDataTableBeforeAjaxFunction(callable $dataTableBeforeAjaxFunction)
    {
        if (!is_callable($dataTableBeforeAjaxFunction)) {
            throw new FatalErrorException(__d("datatables", "the parameter must be a function"));
        }
        $this->dataTableBeforeAjaxFunction = $dataTableBeforeAjaxFunction;
    }

    /**
     * Set a function to be exec after ajax request
     * @param callable $dataTableAfterAjaxFunction
     */
    public function setDataTableAfterAjaxFunction(callable $dataTableAfterAjaxFunction)
    {
        if (!is_callable($dataTableAfterAjaxFunction)) {
            throw new FatalErrorException(__d("datatables", "the parameter must be a function"));
        }
        $this->dataTableAfterAjaxFunction = $dataTableAfterAjaxFunction;
    }

    /**
     * Ajax method to get data dynamically to the DataTables
     * @param string $config
     */
    public function getDataTablesContent($config)
    {
        if (!empty($this->dataTableBeforeAjaxFunction) and is_callable($this->dataTableBeforeAjaxFunction)) {
            call_user_func($this->dataTableBeforeAjaxFunction);
        }

        $this->request->allowMethod('ajax');
        $configName = $config;
        $config = $this->DataTables->getDataTableConfig($configName);
        $params = $this->request->getQuery();
        $this->viewBuilder()->setClassName('DataTables.DataTables');
        $this->viewBuilder()->setTemplate(Inflector::underscore($configName));

        if(empty($this->{$config['table']})) {
            $this->loadModel($config['table']);
        }

        // searching all fields
        $where = [];
        if (!empty($params['search']['value'])) {
            foreach ($config['columns'] as $column) {
                if ($column['searchable'] == true) {
                    $explodedColumnName = explode(".", $column['name']);
                    if (count($explodedColumnName) == 2) {
                        if ($explodedColumnName[0] === $this->{$config['table']}->getAlias()) {
                            $columnType = !empty($this->{$config['table']}->getSchema()->getColumn($explodedColumnName[1])['type']) ? $this->{$config['table']}->getSchema()->getColumn($explodedColumnName[1])['type'] : 'string';
                        } else {
                            $columnType = !empty($this->{$config['table']}->{$explodedColumnName[0]}->getSchema()->getColumn($explodedColumnName[1])['type']) ? $this->{$config['table']}->getSchema()->getColumn($explodedColumnName[1])['type'] : 'string';
                        }
                    } else {
                        $columnType = !empty($this->{$config['table']}->getSchema()->getColumn($column['name'])['type']) ? $this->{$config['table']}->getSchema()->getColumn($column['name'])['type'] : 'string';
                    }
                    switch ($columnType) {
                        case "integer":
                            if (is_numeric($params['search']['value'])) {
                                $where['OR']["{$column['name']}"] = $params['search']['value'];
                            }
                            break;
                        case "decimal":
                            if (is_numeric($params['search']['value'])) {
                                $where['OR']["{$column['name']}"] = $params['search']['value'];
                            }
                            break;
                        case "string":
                            $where['OR']["{$column['name']} like"] = "%{$params['search']['value']}%";
                            break;
                        case "text":
                            $where['OR']["{$column['name']} like"] = "%{$params['search']['value']}%";
                            break;
                        case "boolean":
                            $where['OR']["{$column['name']} like"] = "%{$params['search']['value']}%";
                            break;
                        case "datetime":
                            $where['OR']["{$column['name']} like"] = "%{$params['search']['value']}%";
                            break;
                        default:
                            $where['OR']["{$column['name']} like"] = "%{$params['search']['value']}%";
                            break;
                    }
                }
            }
        }

        // searching individual field
        foreach ($params['columns'] as $paramColumn) {
            $columnSearch = $paramColumn['search']['value'];
            if (!$columnSearch || !$paramColumn['searchable']) {
                continue;
            }

            $explodedColumnName = explode(".", $paramColumn['name']);
            if (count($explodedColumnName) == 2) {
                if ($explodedColumnName[0] === $this->{$config['table']}->getAlias()) {
                    $columnType = !empty($this->{$config['table']}->getSchema()->getColumn($explodedColumnName[1])['type']) ? $this->{$config['table']}->getSchema()->getColumn($explodedColumnName[1])['type'] : 'string';
                } else {
                    $columnType = !empty($this->{$config['table']}->{$explodedColumnName[0]}->getSchema()->getColumn($explodedColumnName[1])['type']) ? $this->{$config['table']}->getSchema()->getColumn($explodedColumnName[1])['type'] : 'string';
                }
            } else {
                $columnType = !empty($this->{$config['table']}->getSchema()->getColumn($paramColumn['name'])['type']) ? $this->{$config['table']}->getSchema()->getColumn($paramColumn['name'])['type'] : 'string';
            }
            switch ($columnType) {
                case "integer":
                    if (is_numeric($params['search']['value'])) {
                        $where[] = [$paramColumn['name'] => $columnSearch];
                    }
                    break;
                case "decimal":
                    if (is_numeric($params['search']['value'])) {
                        $where[] = [$paramColumn['name'] => $columnSearch];
                    }
                    break;
                case 'string':
                    $where[] = ["{$paramColumn['name']} like" => "%$columnSearch%"];
                    break;
                default:
                    $where[] = ["{$paramColumn['name']} like" => "%$columnSearch%"];
                    break;
            }
        }

        $order = [];
        if (!empty($params['order'])) {
            foreach ($params['order'] as $item) {
                $order[$config['columnsIndex'][$item['column']]] = $item['dir'];
            }
        }

        foreach ($config['columns'] as $key => $item) {
            if ($item['database'] == true) {
                $select[] = $key;
            }
        }

        if (!empty($config['databaseColumns'])) {
            foreach ($config['databaseColumns'] as $key => $item) {
                $select[] = $item;
            }
        }

        /** @var array $select */
        $results = $this->{$config['table']}->find($config['finder'], $config['queryOptions'])
            ->select($select)
            ->where($where)
            ->limit($params['length'])
            ->offset($params['start'])
            ->order($order);


        $resultInfo = [
            'draw' => (int)$params['draw'],
            'recordsTotal' => (int)$this->{$config['table']}->find('all', $config['queryOptions'])->count(),
            'recordsFiltered' => (int)$results->count()
        ];

        $this->set([
            'results' => $results,
            'resultInfo' => $resultInfo,
        ]);

        if (!empty($this->dataTableAfterAjaxFunction) and is_callable($this->dataTableAfterAjaxFunction)) {
            call_user_func($this->dataTableAfterAjaxFunction);
        }
    }

}
