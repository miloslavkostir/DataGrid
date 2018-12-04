<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @author	Jakub Holub
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid\DataSource;

use NiftyGrid\FilterCondition;

class NDataSource implements IDataSource
{
	private $table;

	public function __construct(\Nette\Database\Table\Selection $table)
	{
		$this->table = $table;
	}

	public function getData()
	{
		return $this->table;
	}

	public function rowToArray($row)
	{
		return $row->toArray();
	}

	public function getPrimaryKey()
	{
		return $this->table->getPrimary();
	}

	public function getCount($column = "*")
	{
		return $this->table->count($column);
	}

	public function orderData($by, $way)
	{
		$this->table->order($by." ".$way);
	}

	public function limitData($limit, $offset)
	{
		$this->table->limit($limit, $offset);
	}

	public function filterData(array $filters)
	{
		foreach($filters as $filter){
			if($filter["type"] == FilterCondition::WHERE){
				$column = $filter["column"];
				$value = $filter["value"];
				if(!empty($filter["columnFunction"])){
					$column = $filter["columnFunction"]."(".$filter["column"].")";
				}
				$column .= $filter["cond"];
				if(!empty($filter["valueFunction"])){
					$column .= $filter["valueFunction"]."(?)";
				}
				$this->table->where($column, $value);
			}
		}
	}
}
