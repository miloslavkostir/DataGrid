<?php
/**
 * NiftyGrid - DataGrid for Nette
 *
 * @authors		Miloslav Koštíř
 * @copyright	Copyright (c) 2016 Miloslav Koštíř
 * @license     New BSD Licence
 * @link        http://addons.nette.org/cs/niftygrid
 */
namespace NiftyGrid\Components;

use Nette\Localization\ITranslator;


class Translator implements ITranslator
{

	/** @var array */
	private $data = [];

	/** @var callable */
	private $pluralize;


	public function __construct()
	{
		// en - default
		$this->pluralize = function($n) {
			return $n == 1 ? 0 : 1;
		};
	}


	/**
	 * @param \ArrayAccess|array|string $data String - path to JSON file
	 * @param callable $pluralCallback
	 */
	public function setLocalization($data, callable $pluralCallback = NULL)
	{
		if (is_array($data) OR $data instanceof \ArrayAccess) {
			$this->data = $data;
		} else {
			$this->data = json_decode(file_get_contents($data), TRUE);
		}

		if ($pluralCallback !== NULL) {
			$this->pluralize = $pluralCallback;
		}
	}


	/**
	 * @param atring|array $message
	 * @param mixed $count
	 * @return string
	 */
	public function translate($message, $count = NULL)
	{

		if (is_int($count)) {
			$plural = call_user_func($this->pluralize, $count);
			$key = json_encode($message);
			return (isset($this->data[$key]) AND isset($this->data[$key][$plural]))
					? sprintf($this->data[$key][$plural], $count)
					: sprintf($message[$plural], $count);

		} else {
			return isset($this->data[$message]) ? sprintf($this->data[$message], $count) : sprintf($message, $count);

		}
	}

}	
