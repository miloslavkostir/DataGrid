<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @authors		Jakub Holub, Miloslav Koštíř
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid;

use Nette;
use Nette\Application\UI\Presenter;

abstract class Grid extends \Nette\Application\UI\Control
{
	const ROW_FORM = "rowForm";

	const ADD_ROW = "addRow";

	/** @persistent array */
	public $filter = array();

	/** @persistent string */
	public $order;

	/** @persistent int */
	public $perPage = 20;

	/** @persistent int */
	public $activeSubGridId;

	/** @persistent string */
	public $activeSubGridName;

	/** @var array */
	protected $perPageValues = array(20 => 20, 50 => 50, 100 => 100);

	/** @var bool */
	public $paginate = TRUE;

	/** @var string */
	protected $defaultOrder;

	/** @var DataSource\IDataSource */
	protected $dataSource;

	/** @var string */
	protected $primaryKey;

	/** @var string */
	public $gridName;

	/** @var string */
	public $width;

	/** @var bool */
	public $enableSorting = TRUE;

	/** @var int */
	public $activeRowForm;

	/** @var callback */
	public $rowFormCallback;

	/** @var bool */
	public $showAddRow = FALSE;

	/** @var bool */
	public $isSubGrid = FALSE;

	/** @var array */
	public $subGrids = array();

	/** @var callback */
	public $afterConfigureSettings;

	/** @var string */
	protected $templatePath;

	/** @var string */
	public $messageNoRecords = 'Žádné záznamy';

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	public function __construct()
	{
		parent::__construct();
		$this->translator = new Components\Translator;
	}


	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	protected function attached($presenter)
	{
		parent::attached($presenter);

		if (empty($this->translator)) {
			// Must call parent::__construct(). Benevolent yet
			trigger_error('Translator is not set. Method '.get_class($this).'::__construct() or its descendant may not call parent::__construct().', E_USER_NOTICE);
			//throw new Nette\InvalidStateException('Translator is not set. Method '.get_class($this).'::__construct() or its descendant may not call parent::__construct().');
			$this->translator = new Components\Translator;
		}
		
		if(!$presenter instanceof Presenter) return;

		$this->addComponent(New \Nette\ComponentModel\Container(), "columns");
		$this->addComponent(New \Nette\ComponentModel\Container(), "buttons");
		$this->addComponent(New \Nette\ComponentModel\Container(), "globalButtons");
		$this->addComponent(New \Nette\ComponentModel\Container(), "actions");
		$this->addComponent(New \Nette\ComponentModel\Container(), "subGrids");

		if($presenter->isAjax()){
			$this->redrawControl();
		}

		$this->configure($presenter);

		if($this->isSubGrid && !empty($this->afterConfigureSettings)){
			call_user_func($this->afterConfigureSettings, $this);
		}

		if($this->hasActiveSubGrid()){
			$subGrid = $this->addComponent($this['subGrids']->components[$this->activeSubGridName]->getGrid(), "subGrid".$this->activeSubGridName);
			$subGrid->registerSubGrid("subGrid".$this->activeSubGridName);
		}

		if($this->hasActionForm()){
			$actions = array();
			foreach($this['actions']->components as $name => $action){
				$actions[$name] = $action->getAction();
			}
			$this['gridForm'][$this->name]['action']['action_name']->setItems($actions);
		}

		if($this->hasActiveOrder() && $this->hasEnabledSorting()){
			$this->orderData($this->order);
		}
		if(!$this->hasActiveOrder() && $this->hasDefaultOrder() && $this->hasEnabledSorting()){
			if (is_array($this->defaultOrder)) {
				$order = $this->defaultOrder;
			} else {
				$order = explode(" ", $this->defaultOrder);
			}
			$this->dataSource->orderData($order[0], isset($order[1]) ? $order[1] : 'ASC');
		}

		// Check existence of NiftyGrid\DataSource\IDataSource::rowToArray()
		if (!method_exists($this->dataSource, 'rowToArray')) {
			trigger_error('Your data source ' . get_class($this->dataSource) . " doesn't have method rowToArray(). In higher version this will trigger fatal error. Please, implement it (see NiftyGrid\DataSource\NDataSource).", E_USER_WARNING);
		}
	}

	abstract protected function configure($presenter);

	/**
	 * @return void
	 */
	protected function paginate()
	{
		if($this->paginate){
			if($this->hasActiveItemPerPage()){
				if(in_array($this->perPage, $this['gridForm'][$this->name]['perPage']['perPage']->items)){
					$this['gridForm'][$this->name]['perPage']->setDefaults(array("perPage" => $this->perPage));
				}else{
					$items = $this['gridForm'][$this->name]['perPage']['perPage']->getItems();
					$this->perPage = reset($items);
				}
			}else{
				$items = $this['gridForm'][$this->name]['perPage']['perPage']->getItems();
				$this->perPage = reset($items);
			}
			$this->getPaginator()->itemsPerPage = $this->perPage;
		}
	}

	/**
	 * @return void
	 */
	protected function filter()
	{
		if($this->hasActiveFilter()){
			$this->filterData();
			$this['gridForm'][$this->name]['filter']->setDefaults($this->filter);
		}
	}
	

	/**
	 * @param string $subGrid
	 */
	public function registerSubGrid($subGrid)
	{
		if(!$this->isSubGrid){
			$this->subGrids[] = $subGrid;
		}else{
			$this->parent->registerSubGrid($this->name."-".$subGrid);
		}
	}

	/**
	 * @return array
	 */
	public function getSubGrids()
	{
		if($this->isSubGrid){
			return $this->parent->getSubGrids();
		}else{
			return $this->subGrids;
		}
	}

	/**
	 * @param null|string $gridName
	 * @return string
	 */
	public function getGridPath($gridName = NULL)
	{
		if(empty($gridName)){
			$gridName = $this->name;
		}else{
			$gridName = $this->name."-".$gridName;
		}
		if($this->isSubGrid){
			return $this->parent->getGridPath($gridName);
		}else{
			return $gridName;
		}
	}

	public function findSubGridPath($gridName)
	{
		foreach($this->subGrids as $subGrid){
			$path = explode("-", $subGrid);
			if(end($path) == $gridName){
				return $subGrid;
			}
		}
	}

	/**
	 * @param string $columnName
	 * @return \Nette\Forms\IControl
	 * @throws UnknownColumnException
	 */
	public function getColumnInput($columnName)
	{
		if(!$this->columnExists($columnName)){
			throw new UnknownColumnException("Column $columnName doesn't exists.");
		}

		return $this['gridForm'][$this->name]['rowForm'][$columnName];
	}

	/**
	 * @param string $name
	 * @param null|string $label
	 * @param null|string $width
	 * @param null|int $truncate
	 * @return Components\Column
	 * @throws DuplicateColumnException
	 * @return \Nifty\Grid\Column
	 */
	protected function addColumn($name, $label = NULL, $width = NULL, $truncate = NULL)
	{
		if(!empty($this['columns']->components[$name])){
			throw new DuplicateColumnException("Column $name already exists.");
		}
		$this['columns']->addComponent(($column = new Components\Column), $name);
		$column->setName($name)
			->setLabel($label)
			->setWidth($width)
			->setTruncate($truncate)
			->injectParent($this);

		return $column;
	}

	/**
	 * @param string $name
	 * @param null|string $label
	 * @return Components\Button
	 * @throws DuplicateButtonException
	 */
	protected function addButton($name, $label = NULL)
	{
		if(!empty($this['buttons']->components[$name])){
			throw new DuplicateButtonException("Button $name already exists.");
		}
		$this['buttons']->addComponent(($button = new Components\Button), $name);
		if($name == self::ROW_FORM){
			$self = $this;
			$primaryKey = $this->primaryKey;
			$button->setLink(function($row) use($self, $primaryKey){
				return $self->link("showRowForm!", $row[$primaryKey]);
			});
		}
		$button->setLabel($label);
		return $button;
	}


	/**
	 * @param string $name
	 * @param null|string $label
	 * @throws DuplicateGlobalButtonException
	 * @return Components\GlobalButton
	 */
	public function addGlobalButton($name, $label = NULL)
	{
		if(!empty($this['globalButtons']->components[$name])){
			throw new DuplicateGlobalButtonException("Global button $name already exists.");
		}
		$this['globalButtons']->addComponent(($globalButton = new Components\GlobalButton), $name);
		if($name == self::ADD_ROW){
			$globalButton->setLink($this->link("showRowForm!"));
		}
		$globalButton->setLabel($label);
		return $globalButton;
	}

	/**
	 * @param string $name
	 * @param null|string $label
	 * @return Components\Action
	 * @throws DuplicateActionException
	 */
	public function addAction($name, $label = NULL)
	{
		if(!empty($this['actions']->components[$name])){
			throw new DuplicateActionException("Action $name already exists.");
		}
		$this['actions']->addComponent(($action = new Components\Action), $name);
		$action->setName($name)
			->setLabel($label);

		return $action;
	}

	/**
	 * @param string $name
	 * @param null|string $label
	 * @return Components\SubGrid
	 * @throws DuplicateSubGridException
	 */
	public function addSubGrid($name, $label = NULL)
	{
		if(!empty($this['subGrids']->components[$name]) || in_array($name, $this->getSubGrids())){
			throw new DuplicateSubGridException("SubGrid $name already exists.");
		}
		$self = $this;
		$primaryKey = $this->primaryKey;
		$this['subGrids']->addComponent(($subGrid = new Components\SubGrid), $name);
		$subGrid->setName($name)
			->setLabel($label);
		if($this->activeSubGridName == $name){
			$subGrid->setClass("grid-subgrid-close");
			$subGrid->setClass(function($row) use ($self, $primaryKey){
				return $row[$primaryKey] == $self->activeSubGridId ? "grid-subgrid-close" : "grid-subgrid-open";
			});
			$subGrid->setLink(function($row) use ($self, $name, $primaryKey){
				if ($row[$primaryKey] == $self->activeSubGridId) {
					return $self->link('closeSubGrid!');
				} else {
					return $self->link("this", array("activeSubGridId" => $row[$primaryKey], "activeSubGridName" => $name));
				}
			});
		}
		else{
			$subGrid->setClass("grid-subgrid-open")
			->setLink(function($row) use ($self, $name, $primaryKey){
				return $self->link("this", array("activeSubGridId" => $row[$primaryKey], "activeSubGridName" => $name));
			});
		}
		return $subGrid;
	}

	/**
	 * @return array
	 */
	public function getColumnNames()
	{
		$columns = array();
		foreach($this['columns']->components as $column){
			$columns[] = $column->name;
		}
		return $columns;
	}

	/**
	 * @return int $count
	 */
	public function getColsCount()
	{
		$count = count($this['columns']->components);
		if ($this->hasActionForm()) $count++;
		if ($this->hasButtons() || $this->hasFilterForm()) $count++;
		$count += count($this['subGrids']->components);

		return $count;
	}

	/**
	 * @param DataSource\IDataSource $dataSource
	 */
	protected function setDataSource(DataSource\IDataSource $dataSource)
	{
		$this->dataSource = $dataSource;
		$this->primaryKey = $this->dataSource->getPrimaryKey();
	}

	/**
	 * @param string $gridName
	 */
	public function setGridName($gridName)
	{
		$this->gridName = $gridName;
	}

	/**
	 * @param string $width
	 */
	public function setWidth($width)
	{
		$this->width = $width;
	}

	/**
	 * @param string $messageNoRecords
	 */
	public function setMessageNoRecords($messageNoRecords)
	{
		$this->messageNoRecords = $messageNoRecords;
	}

	/**
	 * Simple:
	 * setDefaultOrder('colname'); or setDefaultOrder('colname DESC');
	 * Advanced (corresponds with NiftyGrid\DataSource\IDataSource::orderData():
	 * setDefaultOrder('colname', 'DESC');
	 * @param string $order  Column name or whole ORDER clause
	 * @param string $way
	 */
	public function setDefaultOrder($order, $way = NULL)
	{
		$this->defaultOrder = isset($way) ? [$order, $way] : $order;
	}

	/**
	 * @param array $values
	 * @return array
	 */
	protected function setPerPageValues(array $values)
	{
		$perPageValues = array();
		foreach($values as $value){
			$perPageValues[$value] = $value;
		}
		$this->perPageValues = $perPageValues;
	}

	/**
	 * @return bool
	 */
	public function hasButtons()
	{
		return count($this['buttons']->components) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasGlobalButtons()
	{
		return count($this['globalButtons']->components) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasFilterForm()
	{
		foreach($this['columns']->components as $column){
			if(!empty($column->filterType))
				return TRUE;
		}
		return FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasActionForm()
	{
		return count($this['actions']->components) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasActiveFilter()
	{
		return count($this->filter) ? TRUE : FALSE;
	}

	/**
	 * @param string $filter
	 * @return bool
	 */
	public function isSpecificFilterActive($filter)
	{
		if(isset($this->filter[$filter])){
			return ($this->filter[$filter] != '') ? TRUE : FALSE;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function hasActiveOrder()
	{
		return !empty($this->order) ? TRUE: FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasDefaultOrder()
	{
		return !empty($this->defaultOrder) ? TRUE : FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasEnabledSorting()
	{
		return $this->enableSorting;
	}

	/**
	 * @return bool
	 */
	public function hasActiveItemPerPage()
	{
		return !empty($this->perPage) ? TRUE : FALSE;
	}

	public function hasActiveRowForm()
	{
		return !empty($this->activeRowForm) ? TRUE : FALSE;
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function columnExists($column)
	{
		return isset($this['columns']->components[$column]);
	}

	/**
	 * @param string $subGrid
	 * @return bool
	 */
	public function subGridExists($subGrid)
	{
		return isset($this['subGrids']->components[$subGrid]);
	}

	/**
	 * @return bool
	 */
	public function isEditable()
	{
		foreach($this['columns']->components as $component){
			if($component->editable)
				return TRUE;
		}
		return FALSE;
	}

	/**
	 * @return bool
	 */
	public function hasActiveSubGrid()
	{
		return (!empty($this->activeSubGridId) && !empty($this->activeSubGridName) && $this->subGridExists($this->activeSubGridName)) ? TRUE : FALSE;
	}

	/**
	 * @return mixed
	 * @throws InvalidFilterException
	 * @throws UnknownColumnException
	 * @throws UnknownFilterException
	 */
	protected function filterData()
	{
		$filters = array();
		foreach($this->filter as $name => $value){
			try{
				if(!$this->columnExists($name)){
					throw new UnknownColumnException($this->translator->translate("Column %s doesn't exists", $name));
				}
				if(!$this['columns-'.$name]->hasFilter()){
					throw new UnknownFilterException($this->translator->translate("Column %s doesn't have filter", $name));
				}
				$filterControl = $this['gridForm'][$this->name]['filter'][$name];
				if($filterControl instanceof Nette\Forms\Controls\ChoiceControl && !array_key_exists($value, $filterControl->getItems())){
					throw new UnknownFilterException($this->translator->translate("Column %s doesn't contains value %s", [$this['columns']->getComponent($name)->label, $value]));
				}
				if($filterControl instanceof Nette\Forms\Controls\MultiChoiceControl){
					$value = (array) $value;
					if ($diff = array_diff($value, array_keys($filterControl->getItems()))) {

						throw new UnknownFilterException($this->translator->translate("Column %s doesn't contains values %s", [$this['columns']->getComponent($name)->label, implode(',', $diff)]));
					}
				}

				$type = $this['columns-'.$name]->getFilterType();
				$filter = FilterCondition::prepareFilter($value, $type);

				if(method_exists("\\NiftyGrid\\FilterCondition", $filter["condition"])){
					$filter = call_user_func("\\NiftyGrid\\FilterCondition::".$filter["condition"], $filter["value"]);
					if(!empty($this['gridForm'][$this->name]['filter'][$name])){
						$filter["column"] = $name;
						if(!empty($this['columns-'.$filter["column"]]->tableName)){
							$filter["column"] = $this['columns-'.$filter["column"]]->tableName;
						}
						$filters[] = $filter;
					}else{
						throw new InvalidFilterException("Invalid filter");
					}
				}else{
					throw new InvalidFilterException("Invalid filter");
				}
			}
			catch(UnknownColumnException $e){
				unset($this->filter[$name]);
				$this->flashMessage($e->getMessage(), "grid-error");
			}
			catch(UnknownFilterException $e){
				unset($this->filter[$name]);
				$this->flashMessage($e->getMessage(), "grid-error");
			}
		}
		return $this->dataSource->filterData($filters);
	}

	/**
	 * @param string $order
	 * @throws InvalidOrderException
	 */
	protected function orderData($order)
	{
		try{
			$order = explode(" ", $order);
			if(in_array($order[0], $this->getColumnNames()) && in_array($order[1], array("ASC", "DESC")) && $this['columns-'.$order[0]]->isSortable()){
				if(!empty($this['columns-'.$order[0]]->tableName)){
					$order[0] = $this['columns-'.$order[0]]->tableName;
				}
				$this->dataSource->orderData($order[0], $order[1]);
			}else{
				throw new InvalidOrderException($this->translator->translate("Invalid sorting"));
			}
		}
		catch(InvalidOrderException $e){
			$this->flashMessage($e->getMessage(), "grid-error");
			$this->order = NULL;
			if (!$this->presenter->isAjax()) {
				$this->redirect("this");
			}
		}
	}

	/**
	 * @return int
	 */
	protected function getCount()
	{
		if(!$this->dataSource) throw new GridException("DataSource not yet set");
		if($this->paginate){
			$count = $this->dataSource->getCount();
			$this->getPaginator()->itemCount = $count;
			$this->dataSource->limitData($this->getPaginator()->itemsPerPage, $this->getPaginator()->offset);
			return $count;
		}else{
			$count = $this->dataSource->getCount();
			$this->getPaginator()->itemCount = $count;
			return $count;
		}
	}

	/**
	 * @return GridPaginator
	 */
	protected function createComponentPaginator()
	{
		return new GridPaginator;
	}

	/**
	 * @return \Nette\Utils\Paginator
	 */
	public function getPaginator()
	{
		return $this['paginator']->paginator;
	}

	/**
	 * @return \NiftyGrid\GridPaginator
	 */
	public function getGridPaginator()
	{
		return $this['paginator'];
	}

	/**
	 * @param int $page
	 */
	public function handleChangeCurrentPage($page)
	{
		if($this->presenter->isAjax()){
			$this->redirect("this", array("paginator-page" => $page));
		}
	}

	/**
	 * @param int $perPage
	 */
	public function handleChangePerPage($perPage)
	{
		if($this->presenter->isAjax()){
			$this->redirect("this", array("perPage" => $perPage));
		}
	}

	/**
	 * @param string $column
	 * @param string $term
	 */
	public function handleAutocomplete($column, $term)
	{
		if($this->presenter->isAjax()){
			if(!empty($this['columns']->components[$column]) && $this['columns']->components[$column]->autocomplete){
				$condition = $this['columns']->components[$column]->getAutocompleteCondition();
				if ($condition === FilterCondition::CONTAINS) {
					$this->filter[$column] = $term;
				} else {
					$this->filter[$column] = $term."%";
				}
				$this->filterData();
				$this->dataSource->limitData($this['columns']->components[$column]->getAutocompleteResults(), NULL);
				$data = $this->dataSource->getData();
				$results = array();
				foreach($data as $row){
					$value = $row[$column];
					if(!in_array($value, $results)){
						$results[] = $row[$column];
					}
				}
				$this->presenter->payload->payload = $results;
				$this->presenter->sendPayload();
			}
		}
	}

	/** @deprecated */
	public function handleAddRow()
	{
		$this->showAddRow = TRUE;
	}

	/**
	 * @param int $id
	 */
	public function handleShowRowForm($id = NULL)
	{
		if ($id) {
			$this->activeRowForm = $id;
		} else {
			$this->showAddRow = TRUE;
		}
	}

	public function handleCloseRowForm()
	{
		$this->showAddRow = FALSE;
		$this->activeRowForm = NULL;
	}

	public function handleRemoveFilter()
	{
		$this->filter = [];
	}

	public function handleCloseSubgrid()
	{
		$this->activeSubGridId = NULL;
		$this->activeSubGridName = NULL;
	}

	/**
	 * @param $callback
	 */
	public function setRowFormCallback($callback)
	{
		$this->rowFormCallback = $callback;
	}

	/**
	 * @param int $id
	 * @return \Nette\Forms\Controls\Checkbox
	 */
	public function assignCheckboxToRow($id)
	{
		return $this['gridForm'][$this->name]['action']->addCheckbox("row_".$id)
			->setAttribute('class', 'grid-action-checkbox')
			->setValue(NULL)
			->getControl();
	}

	protected function createComponentGridForm()
	{
		$form = new \Nette\Application\UI\Form;
		$form->method = "POST";
		$form->getElementPrototype()->class[] = "grid-gridForm";
		$form->setTranslator($this->translator);

		$form->addContainer($this->name);

		$form[$this->name]->addContainer("rowForm");
		$form[$this->name]['rowForm']->addSubmit("send","Save");
		$form[$this->name]['rowForm']['send']->getControlPrototype()->addClass("grid-editable");

		$form[$this->name]->addContainer("filter");
		$form[$this->name]['filter']->addSubmit("send","Filter")
			->setValidationScope(FALSE);

		$form[$this->name]->addContainer("action");
		$form[$this->name]['action']->addSelect("action_name","Selected:");
		$form[$this->name]['action']->addSubmit("send","Confirm")
			->setValidationScope(FALSE)
			->getControlPrototype()
			->data("select", $form[$this->name]["action"]["action_name"]->getControl()->name);

		$form[$this->name]->addContainer('perPage');
		$form[$this->name]['perPage']->addSelect("perPage","Records per page:", $this->perPageValues)
			->setTranslator(NULL)
			->getControlPrototype()
			->addClass("grid-changeperpage")
			->data("gridname", $this->getGridPath())
			->data("link", $this->link("changePerPage!"));
		$form[$this->name]['perPage']->addSubmit("send","OK")
			->setValidationScope(FALSE)
			->getControlPrototype()
			->addClass("grid-perpagesubmit");

		$form->setTranslator($this->getTranslator());

		$form->onSuccess[] = [$this, "processGridForm"];

		return $form;
	}

	/**
	 * @param array $values
	 */
	public function processGridForm($values)
	{
		$values = $values->getHttpData();
		foreach($values as $gridName => $grid){
			foreach($grid as $section => $container){
				foreach($container as $key => $value){
					if($key == "send"){
						unset($container[$key]);
						$subGrids = $this->subGrids;
						foreach($subGrids as $subGrid){
							$path = explode("-", $subGrid);
							if(end($path) == $gridName){
								$gridName = $subGrid;
								break;
							}
						}
						if($section == "filter"){
							$this->filterFormSubmitted($values);
							break 3;
						}
						$section = ($section == "rowForm") ? "row" : $section;
						if(method_exists($this, $section."FormSubmitted")){
							call_user_func("self::".$section."FormSubmitted", $container, $gridName);
						}elseif(!$this->presenter->isAjax()){
							$this->redirect("this");
						}
						break 3;
					}
				}
			}
		}
	}

	/**
	 * @param array $values
	 * @param string $gridName
	 */
	public function rowFormSubmitted($values, $gridName)
	{
		$subGrid = ($gridName == $this->name) ? FALSE : TRUE;
		if($subGrid){
			call_user_func($this[$gridName]->rowFormCallback, (array) $values);
		}else{
			call_user_func($this->rowFormCallback, (array) $values);
		}
		if (!$this->presenter->isAjax()) {
			$this->redirect("this");
		}
	}

	/**
	 * @param array $values
	 * @param string $gridName
	 */
	public function perPageFormSubmitted($values, $gridName)
	{
		if ($gridName == $this->name) {
			$this->perPage = $values["perPage"];
		} else {
			$this[$gridName]->perPage = $values["perPage"];
		}

		if (!$this->presenter->isAjax()) {
			$this->redirect("this");
		}
	}

	/**
	 * @param array $values
	 * @param string $gridName
	 * @throws NoRowSelectedException
	 */
	public function actionFormSubmitted($values, $gridName)
	{
		try{
			$rows = array();
			foreach($values as $name => $value){
				if(\Nette\Utils\Strings::startsWith($name, "row")){
					$vals = explode("_", $name);
					if((boolean) $value){
						$rows[] = $vals[1];
					}
				}
			}
			$subGrid = ($gridName == $this->name) ? FALSE : TRUE;
			if(!count($rows)){
				throw new NoRowSelectedException($this->translator->translate('No row selected'));
			}
			if($subGrid){
				call_user_func($this[$gridName]['actions']->components[$values['action_name']]->getCallback(), $rows);
			}else{
				call_user_func($this['actions']->components[$values['action_name']]->getCallback(), $rows);
			}
			if (!$this->presenter->isAjax()) {
				$this->redirect("this");
			}
		}
		catch(NoRowSelectedException $e){
			if($subGrid){
				$this[$gridName]->flashMessage($e->getMessage(),"grid-error");
			}else{
				$this->flashMessage($e->getMessage(),"grid-error");
			}
			if (!$this->presenter->isAjax()) {
				$this->redirect("this");
			}
		}
	}

	/**
	 * @param array $values
	 */
	public function filterFormSubmitted($values)
	{
		unset($values['do'], $values['_do']);
		foreach($values as $gridName => $grid){
			$isSubGrid = ($gridName == $this->name) ? FALSE : TRUE;
			foreach($grid['filter'] as $name => $value){
				if($value != ''){
					if($name == "send"){
						continue;
					}
					if($isSubGrid){
						$gridName = $this->findSubGridPath($gridName);
						$this[$gridName]->filter[$name] = $value;
					}else{
						$this->filter[$name] = $value;
					}
				}
			}
			if($isSubGrid){
				$this[$gridName]->getPaginator()->page = NULL;
				if (empty($this[$gridName]->filter)) {
					$this[$gridName]->filter = array();
				}
			} else {
				$this->getPaginator()->page = NULL;
				if (empty($this->filter)) {
					$this->filter = array();
				}
			}
		}
		if (!$this->presenter->isAjax()) {
			$this->redirect("this");
		}
	}

	/**
	 * @param string $templatePath
	 */
	protected function setTemplate($templatePath)
	{
		$this->templatePath = $templatePath;
	}

	public function render()
	{
		$this->paginate();
		$this->filter();
		$count = $this->getCount();
		$this->getPaginator()->itemCount = $count;
		$this->template->results = $count;
		$this->template->columns = $this['columns']->components;
		$this->template->buttons = $this['buttons']->components;
		$this->template->globalButtons = $this['globalButtons']->components;
		$this->template->subGrids = $this['subGrids']->components;
		$this->template->paginate = $this->paginate;
		$this->template->colsCount = $this->getColsCount();
		$rows = $this->dataSource->getData();
		$this->template->rows = $rows;
		$this->template->primaryKey = $this->primaryKey;
		if($this->hasActiveRowForm()){
			$row = $rows[$this->activeRowForm];
			if (method_exists($this->dataSource, 'rowToArray')) {
				$row = $this->dataSource->rowToArray($row);
			} elseif (method_exists($row, 'toArray')) {
				// deprecated
				$row = $rows[$this->activeRowForm]->toArray();
			}
			foreach($row as $name => $value){
				if($this->columnExists($name) && !empty($this['columns']->components[$name]->formRenderer)){
					$row[$name] = call_user_func($this['columns']->components[$name]->formRenderer, $row);
				}
				if(isset($this['gridForm'][$this->name]['rowForm'][$name])){
					$input = $this['gridForm'][$this->name]['rowForm'][$name];
					if($input instanceof \Nette\Forms\Controls\SelectBox){
						$items = $this['gridForm'][$this->name]['rowForm'][$name]->getItems();
						if(in_array($row[$name], $items)){
							$row[$name] = array_search($row[$name], $items);
						}
					}
				}
			}
			$this['gridForm'][$this->name]['rowForm']->setDefaults($row);
			$this['gridForm'][$this->name]['rowForm']->addHidden($this->primaryKey, $this->activeRowForm);
		}
		if($this->paginate){
			$this->template->viewedFrom = ((($this->getPaginator()->getPage()-1)*$this->perPage)+1);
			$this->template->viewedTo = ($this->getPaginator()->getLength()+(($this->getPaginator()->getPage()-1)*$this->perPage));
		}
		$templatePath = !empty($this->templatePath) ? $this->templatePath : __DIR__."/templates/grid.latte";

		if ($this->getTranslator() instanceof \Nette\Localization\ITranslator) {
			$this->template->setTranslator($this->getTranslator());
		}

		$this->template->filters = $this->getFilterList();

		$this->template->setFile($templatePath);
		$this->template->render();
	}

	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @return Grid
	 */
	public function setTranslator(\Nette\Localization\ITranslator $translator)
	{
		$this->translator = $translator;

		return $this;
	}

	/**
	 * @return \Nette\Localization\ITranslator|null
	 */
	public function getTranslator()
	{
		if($this->translator instanceof \Nette\Localization\ITranslator)
			return $this->translator;

		return null;
	}

	/**
	 * @return array
	 */
	private function getFilterList()
	{
		$filters = NULL;

		foreach ($this->filter as $aspect => $filter) {
			if (!isset($this['columns']->components[$aspect]) OR !isset($this['gridForm'][$this->name]['filter'][$aspect])) {
				continue;
			}

			$control = $this['gridForm'][$this->name]['filter'][$aspect];

			if (isset($control->items)) {
				// SELECTS
				$value = array();
				foreach ((array) $filter as $f) {
					if ($f !== "" AND isset($control->items[$f])) {
						$value[] = $control->items[$f];
					} 
				}
				$value = implode(', ', $value);

			} else {
				$value = $filter;
			}

			$filters[] = array('label' => $this['columns']->getComponent($aspect)->label, 'value' => $value);
		}

		return $filters;
	}

}
