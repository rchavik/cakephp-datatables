<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DataTables\Controller\Component;

use Cake\Controller\Component;
use Cake\Utility\Inflector;

/**
 * CakePHP DataTableConfigComponent
 * @author allan
 */
class DataTablesConfigComponent extends Component
{

    private $dataTableConfig = [];
    private $currentConfig   = null;

    public function initialize(array $config)
    {
        $this->dataTableConfig = &$config['DataTablesConfig'];
        parent::initialize($config);
    }

    /**
     * Set initial config name
     * @param string $name
     * @return $this
     */
    public function setConfig($name)
    {
        $this->currentConfig                   = $name;
        $this->dataTableConfig[$name]['id']    = 'dt' . $name;
        $this->dataTableConfig[$name]['table'] = $name;
        $this->dataTableConfig[$name]['queryOptions'] = [];
        return $this;
    }

    /**
     * Set a custom table name. Default is config name
     * @param string $name
     * @return $this
     */
    public function table($name)
    {
        $this->dataTableConfig[$this->currentConfig]['table'] = $name;

        return $this;
    }

    /**
     * Set a column at datatable config
     * @param type $name
     * @param array $options
     * @return DataTablesConfigComponent
     */
    public function column($name, array $options = [])
    {
        if (!empty($options['order']))
        {
            if (!in_array($options['order'], ['asc', 'desc']))
            {
                unset($options['order']);
            }
        }
        $options += [
            'label'          => $name,
            'database'       => true,
            'searchable'     => true,
            'orderable'      => true,
            'className'      => null,
            'orderDataType'  => 'dom-text',
            'type'           => 'text',
            'name'           => $name,
            'visible'        => true,
            'width'          => null,
            'defaultContent' => null,
            'contentPadding' => null,
            'cellType'       => 'td',
        ];

        $this->dataTableConfig[$this->currentConfig]['columns'][$name] = $options;
        $this->dataTableConfig[$this->currentConfig]['columnsIndex'][] = $name;
        return $this;
    }

    /**
     * Set a database column to use in data render
     * @param string $name
     * @return $this
     */
    public function databaseColumn($name)
    {
        $this->dataTableConfig[$this->currentConfig]['databaseColumns'][] = $name;

        return $this;
    }

    /**
     * Set DataTables general configs
     * @param string $options
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->dataTableConfig[$this->currentConfig]['options'] = $options;
        return $this;
    }
    
    public function queryOptions(array $options = [])
    {
        $this->dataTableConfig[$this->currentConfig]['queryOptions'] = $options;
        return $this;
    }

}