<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @author	Jakub Holub
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid\Components;

use Nette\Utils\Html,
	NiftyGrid\Grid; // For constant only

class GlobalButton extends \Nette\Application\UI\PresenterComponent
{
	/** @var callback|string */
	private $label;

	/** @var callback|string */
	private $link;

	/** @var callback|string */
	private $text;

	/** @var callback|string */
	private $target;

	/** @var callback|string */
	private $class;

	/** @var bool */
	private $ajax = TRUE;

	/** @var callback|string */
	private $dialog;

	/** @var callback|string */
	private $show = TRUE;

	/** @var array */
	private $attributes = [];


	/**
	 * @param string $label
	 * @return Button
	 */
	public function setLabel($label)
	{
		$this->label = $label;

		return $this;
	}


	/**
	 * @return string
	 */
	private function getLabel()
	{
		if(is_callable($this->label)){
			return call_user_func($this->label);
		}
		return $this->label;
	}


	/**
	 * @param callback|string $link
	 * @return Button
	 */
	public function setLink($link)
	{
		$this->link = $link;

		return $this;
	}


	/**
	 * @return string
	 */
	private function getLink()
	{
		if(is_callable($this->link)){
			return call_user_func($this->link);
		}
		return $this->link;
	}


	/**
	 * @param $text
	 * @return mixed
	 */
	public function setText($text)
	{
		$this->text = $text;

		return $this;
	}


	/**
	 * @return string
	 */
	private function getText()
	{
		if(is_callable($this->text)){
			return call_user_func($this->text);
		}
		return $this->text;
	}


	/**
	 * @param callback|string $target
	 * @return Button
	 */
	public function setTarget($target)
	{
		$this->target = $target;

		return $this;
	}


	/**
	 * @return callback|mixed|string
	 */
	private function getTarget()
	{
		if(is_callable($this->target)){
			return call_user_func($this->target);
		}
		return $this->target;
	}


	/**
	 * @param callback|string $class
	 * @return Button
	 */
	public function setClass($class)
	{
		$this->class = $class;

		return $this;
	}


	/**
	 * @return callback|mixed|string
	 */
	private function getClass()
	{
		if(is_callable($this->class)){
			return call_user_func($this->class);
		}
		return $this->class;
	}


	/**
	 * @param bool $ajax
	 * @return Button
	 */
	public function setAjax($ajax = TRUE)
	{
		$this->ajax = $ajax;

		return $this;
	}


	/**
	 * @param callback|string $message
	 * @return Button
	 */
	public function setConfirmationDialog($message)
	{
		$this->dialog = $message;

		return $this;
	}


	/**
	 * @return callback|mixed|string
	 */
	public function getConfirmationDialog()
	{
		if(is_callable($this->dialog)){
			return call_user_func($this->dialog);
		}
		return $this->dialog;
	}


	/**
	 * @return bool
	 */
	private function hasConfirmationDialog()
	{
		return (!empty($this->dialog)) ? TRUE : FALSE;
	}


	/**
	 * @param callback|string $show
	 * @return Button
	 */
	public function setShow($show)
	{
		$this->show = $show;

		return $this;
	}


	/**
	 * @return callback|mixed|string
	 */
	public function getShow()
	{
		if(is_callable($this->show)){
			return (boolean) call_user_func($this->show);
		}
		return $this->show;
	}


	/**
	 * @param string $attr
	 * @param callback|mixed $value
	 * @return Button
	 */
	public function addAttribute($attr, $value)
	{
		$this->attributes[$attr] = $value;

		return $this;
	}


	/**
	 * @return callback|mixed
	 */
	public function getAttribute($attr)
	{
		if (!array_key_exists($attr, $this->attributes)) {
			return NULL;
		}
		if (is_callable($this->attributes[$attr])){
			return call_user_func($this->attributes[$attr]);
		}
		return $this->attributes[$attr];
	}


	/**
	 * @return array
	 */
	public function getAttributes()
	{
		$attrs = [];
		foreach (array_keys($this->attributes) as $attr) {
			$attrs[$attr] = $this->getAttribute($attr);
		}
		return $attrs;
	}


	public function render()
	{
		if (!$this->getShow()) {
			return FALSE;
		}

		$el = Html::el("a")
			->href($this->getLink())
			->setText($this->getText())
			->setClass($this->getClass())
			->addClass("grid-button")
			->addClass("grid-global-button")
			->setTitle($this->getLabel())
			->setTarget($this->getTarget());

		$el->addAttributes($this->getAttributes());

		if ($this->ajax) {
			$el->addClass("grid-ajax");
		}
		
		if ($this->getName() == Grid::ADD_ROW) {
			$el->addClass("grid-add-row");
		}

		if ($this->hasConfirmationDialog()) {
			$el->addClass("grid-confirm")
				->addData("grid-confirm", $this->getConfirmationDialog());
		}
	
		echo $el;
	}
}
