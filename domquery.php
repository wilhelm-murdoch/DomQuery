<?php

define('SAVE_MODE_DOM',    1);
define('SAVE_MODE_STRING', 2);
define('SAVE_MODE_SIMPLE', 4);

/***
 * DomQuery
 *
 * Extends the functionality of DOMDocument to allow simple manipulation of
 * a provided DOM using the power of XPath. This class was heavily inspired
 * by the jQuery framework. Instead of 'selectors' you're using 'xpath' to
 * search and manipulate elements.
 *
 * @package DomQuery
 * @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @copyright Copyright (c) 2010, Daniel Wilhelm II Murdoch
 * @link http://www.thedrunkenepic.com
 * @version 1.2.0
 ***/
class DomQuery extends DOMDocument implements Countable
{
   /**
	* Holds the results of the latest XPath expression.
	* @access Public
	* @var Object
	*/
	private $Results;


   // ! Constructor Method

   /**
	* Instantiates class and defines instance variables.
	*
	* @param String $version  The version of the document.
	* @param String $encoding The character encoding of the document.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Void
	*/
	public function __construct($version = '1.0', $encoding = 'utf-8')
	{
		parent::__construct($version, $encoding);

		$this->preserveWhiteSpace = false;
		$this->formatOutput       = true;

		$this->Results = array();
	}


   // ! Executor Method

   /**
	* Magic method used to throw a catchable exception when calling a non-existant method.
	*
	* <usage>
	*     try
	*     {
	*         $DomQuery = new DomQuery;
	*
	*         $DomQuery->i_do_not_exist();
	*     }
	*     catch(DOMException $Exception)
	*     {
	*         echo $Exception->getMessage();
	*     }
	* </usage>
	*
	* @param String $method    The name of the method being called.
	* @param Array  $arguments Any arguments passed through to this method.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Boolean
	*/
	public function __call($method, $arguments)
	{
		throw new DOMException("Method `DomQuery::{$method}()` does not exist.");

		return false;
	}


   // ! Executor Method

   /**
	* Magic method used to access private properties. This allows users to fetch, but not modify,
	* private properties.
	*
	* @param String $method    The name of the method being called.
	* @param Array  $arguments Any arguments passed through to this method.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Boolean
	*/
	public function __get($property)
	{
		if(false == isset($this->$property))
		{
			throw new DOMException("Property `DomQuery::\${$property}` does not exist.");
		}

		return $this->$property;
	}


   // ! Executor Method

   /**
	* Returns the contents of the DOM as an XML string.
	*
	* <usage>
	*     $DomQuery = new DomQuery;
	*
	*     echo $DomQuery->load('<root><foo>foo</foo></root>');
	* </usage>
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return String
	*/
	public function __toString()
	{
		return $this->saveXml();
	}


   // ! Executor Method

   /**
	* Loads source data into the DOMDocument object. You may optionally specify an initial XPath
	* expression to apply to the source. If you set $return to TRUE, it will return an instance
	* of XPathResultIterator which will contain the results of the applied expression.
	*
	* <usage>
	*     try
	*     {
	*         $DomQuery = new DomQuery;
	*
	*         $DomQuery->load('<root><foo>foo</foo></root>', '//root/foo');
	*     }
	*     catch(DOMException $Exception)
	*     {
	*         echo $Exception->getMessage();
	*     }
	* </usage>
	*
	* @param String  $source Source data to pass into the DOMDocument object.
	* @param String  $path   An XPath expression may be immediately executed after loading XML.
	* @param Boolean $return Return the result set rather than a self instance.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function load($source, $path = null, $return = false)
	{
		if(false == $this->loadXml($source))
		{
			throw new DOMException('XML source could not be loaded into the DOM.');
		}

		if(false == is_null($path))
		{
			$this->Results = $this->path($path, true);

			if($return)
			{
				return $this->Results;
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Takes the current result set of the latest XPath expression and returns an entirely new
	* XML document containing said results. May also use a previously returned instance of
	* XPathResultIterator to create the document. Depending one which constant you use, this
	* method can return the results as an XML string, DOMDocument instance or an array containing
	* instances of PHP's SimpleXmlElement class.
	*
	* <usage>
	*     $DomQuery = new DomQuery;
	*
	*     $DomQuery->load('<root><foo>foo</foo></root>');
	*
	*     $xml_string = $DomQuery->save(SAVE_MODE_STRING);
	*
	*     $DOMDocument = $DomQuery->save(SAVE_MODE_DOM);
	*
	*     foreach($DomQuery->save(SAVE_MODE_DOM) as $Xml)
	*     {
	*         echo $Xml->root->foo;
	*     }
	* </usage>
	*
	* @param Integer $mode                Determines the mode in which to save the output (SAVE_MODE_DOM | SAVE_MODE_SIMPLE | SAVE_MODE_STRING).
	* @param Object  $XPathResultIterator Optional instance of XPathResultIterator.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Mixed
	*/
	public function save($mode = SAVE_MODE_STRING, &$Context = null)
	{
		$Dom = new parent($this->version, $this->encoding);

		$Dom->preserveWhiteSpace = false;
		$Dom->formatOutput       = true;

		if(is_object($Context))
		{
			if($Context instanceof XPathResultIterator)
			{
				foreach($Context as $Result)
				{
					$Result = $Dom->importNode($Result, true);

					$Dom->appendChild($Result->cloneNode(true));
				}
			}
			else if($Context instanceof DOMDocument)
			{
				$Dom = &$Context;
			}
			else
			{
				throw new DOMException('Object `' . get_class($Context) . '` is not supported.');
			}
		}
		else
		{
			foreach($this->Results as $Result)
			{
				$Result = $Dom->importNode($Result, true);

				$Dom->appendChild($Result->cloneNode(true));
			}
		}

		switch($mode)
		{
			case SAVE_MODE_DOM:

				return $Dom;

				break;

			case SAVE_MODE_SIMPLE:

				$Results = $Context instanceof XPathResultIterator ? $Context : $this->Results;

				if(count($Results) == 1 || $Context instanceof DOMDocument)
				{
					return array(new SimpleXmlElement($Dom->saveXml()));
				}

				$return = array();

				foreach($Results as $Result)
				{
					$Dom = new parent($this->version, $this->encoding);

					$Dom->appendChild($Dom->importNode($Result, true));

					$return[] = new SimpleXmlElement($Dom->saveXml());

					unset($Dom);
				}

				return $return;

				break;

			default:
			case SAVE_MODE_STRING:

				return $Dom->saveXml();

				break;
		}
	}


   // ! Executor Method

   /**
	* Simply returns the number of matched results from the last XPath query. May also
	* use a previously returned instance of XPathResultIterator.
	*
	* <usage>
	*     $DomQuery = new DomQuery;
	*
	*     echo $DomQuery->load('<root><foo>foo</foo></root>', '//root')->count();
	*
	*     echo $DomQuery->count();
	*
	*     echo count($DomQuery);
	*
	*     echo count($XPathResultIterator);
	* </usage>
	*
	* @param Object $XPathResultIterator Optional instance of XPathResultIterator.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Integer
	*/
	public function count(XPathResultIterator $XPathResultIterator = null)
	{
		return count(is_null($XPathResultIterator) ? $this->Results : $XPathResultIterator);
	}


   // ! Executor Method

   /**
	* Applies an XPath query to the current document. If $return is set to TRUE, this method
	* will return an instance of XPathResultIterator containing the results. You may also
	* run an XPath expression on an existing DOMNode instance.
	*
	* <usage>
	*     $DomQuery = new DomQuery;
	*
	*     $DomQuery->load('<root><foo>foo</foo></root>');
	*
	*     $DomQuery->path('//root/foo');
	*
	*     $XPathResultIterator = $DomQuery->path('//root/foo', true);
	* </usage>
	*
	* @param String  $path    XPath query to execute.
	* @param Boolean $return  Return the result set rather than a self instance.
	* @param Object  $Context You may run an XPath query on a specific element.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function path($path, $return = false, DOMNode $Context = null)
	{
		$XPath = new DOMXPath($this);

		if($Context)
		{
			if($return)
			{
				return new XPathResultIterator($XPath->query($path, $Context));
			}

			$this->Results = new XPathResultIterator($XPath->query($path, $Context));

			return $this;
		}

		if($return)
		{
			return new XPathResultIterator($XPath->query($path));
		}

		$this->Results = new XPathResultIterator($XPath->query($path));

		return $this;
	}


   // ! Executor Method

   /**
	* Apply a user-defined callback function to every element within
	* a result set. This can be an external function or a method of a
	* class. All arguments passed through this method will be passed into
	* the callback function. The first argument passed through the callback
	* will always be the current result's context. The context argument
	* is an array that contains the current document, current element and
	* complete result set of the last xpath query all by reference.
	*
	* The first parameter passed to the callback will be an array containing
	* the following values:
	*
	* <code>
	* array
	* (
	*     'results'  => &$this->Results,       // The current result set of the last XPath expression
	*     'element'  => $Result,               // The currently iterated element
	*     'position' => $this->Results->key(), // The currently itereted element's position within the result set
	*     'context'  => &$this                 // Instance of DomQuery
	* )
	* </code>
	*
	* All of the following examples are acceptable.
	*
	* <usage>
	*     $DomQuery = new DomQuery;
	*
	*     $DomQuery->load('<root><foo>foo</foo></root>');
	*
	*     $DomQuery->walk('my_function', 'arg_one', 'arg_two');
	*
	*     $DomQuery->walk(array('my_static_class', 'my_static_method'), 'arg_one', 'arg_two');
	*
	*     $DomQuery->walk(array($Instance, 'my_method'), 'arg_one', 'arg_two');
	* </usage>
	*
	* @param String | Array $callback The callback method or function to apply result elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function walk($callback)
	{
		$arguments = func_get_args();

		array_shift($arguments);

		foreach($this->Results as $Result)
		{
			if(is_array($callback) && sizeof($callback) == 2)
			{
				if(false == class_exists($callback[0]))
				{
					throw new DOMException("Class `{$callback[0]}` does not exist.");
				}
				else if(false == method_exists($callback[0], $callback[1]))
				{
					throw new DOMException("Method `{$callback[0]}::{$callback[1]}()` does not exist.");
				}
				else if(false == is_callable($callback))
				{
					throw new DOMException("Method `{$callback[0]}::{$callback[1]}()` is not callable.");
				}
			}
			else if(false == function_exists($callback))
			{
				throw new DOMException("Function `{$callback}()` does not exist.");
			}

			array_unshift($arguments, array
			(
				'results'  => &$this->Results,
				'element'  => $Result,
				'position' => $this->Results->key(),
				'context'  => &$this
			));

			call_user_func_array($callback, $arguments);

			array_shift($arguments);
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Applies a runtime-created lambda function to every element within a result set.
	* Just like the 'walk' method, you can apply any number of arguments to the lambda
	* function. Unlike 'walk', though, you cannot currently name them. This method
	* names them for you using a generic convention.
	*
	* The first parameter passed to the callback will be an array containing
	* the following values:
	*
	* <code>
	* array
	* (
	*     'results'  => &$this->Results,       // Reference of the current result set of the last XPath expression
	*     'element'  => $Result,               // The currently iterated element
	*     'position' => $this->Results->key(), // The currently itereted element's position within the result set
	*     'context'  => &$this                 // Reference of DomQuery instance
	* )
	* </code>
	*
	* All of the following examples are acceptable.
	*
	* <usage>
	*     $DomQuery = new DomQuery;
	*
	*     $DomQuery->load('<root><foo>foo</foo></root>');
	*
	*     //Print the name of first node of each matched element:
	*
	*     $function = '$arguments = func_get_args(); echo $arguments[0][element]->nodeName;';
	*
	*     $DomQuery->each($function, 'arg_one', 'arg_two');
	* </usage>
	*
	* The arguments valued 'foo' and 'bar' will be accessed within the lambda function
	* as '$param1' and '$param2' respectively. This may change in future versions or
	* when PHP 5.3.x is more widely-used.
	*
	* @param String $function Content of the function to execute.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function each($function)
	{
		$arguments = func_get_args();

		array_shift($arguments);


		// We need to pass the names of the arguments to the lambda function
		// so, we're going to have to build a string out of all the arguments
		// that have been passed into this method:

		$lambda_arguments = array('$context');

		for($i = 1; $i < (sizeof($arguments) + 1); $i++)
		{
			$lambda_arguments[] = "\$param{$i}";
		}

		$lambda_arguments = implode(',', $lambda_arguments);


		// Now we iterate through the nodes our last XPath pattern discoverd
		// and apply the runtime-created lambda function to each one:

		foreach($this->Results as $Result)
		{
			array_unshift($arguments, array
			(
				'results'  => &$this->Results,
				'element'  => $Result,
				'position' => $this->Results->key(),
				'context'  => &$this
			));

			$func = create_function($lambda_arguments, $function);

			call_user_func_array($func, $arguments);

			array_shift($arguments);
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Copies the elements of the last XPath expression to all results of $path.
	*
	* @param String $path_destination The destination of the copied elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function copy($path_from, $path_to)
	{
		foreach($this->path($path_to, true) as $To)
		{
			$this->path($path_from);

			foreach($this->Results as $From)
			{
				$To->appendChild($From->cloneNode(true));
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Clears the contents of all matched elements.
	*
	* @param none
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function clear()
	{
		foreach($this->Results as $Result)
		{
			$Result->nodeValue = '';
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Completely removes all matched elements.
	*
	* @param none
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function remove()
	{
		foreach($this->Results as $Result)
		{
			$Result->parentNode->removeChild($Result);
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Append content to the inside of every matched element.
	*
	* @param Object $Element The content to append.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function append(DOMElement $Element)
	{
		$Element = $this->importNode($Element, true);

		foreach($this->Results as $Result)
		{
			$Result->appendChild($Element->cloneNode(true));
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Append all of the matched elements to another, specified, set of elements.
	*
	* @param String $path The destination path of all matched elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function appendTo($path)
	{
		foreach($this->path($path, true) as $Destination)
		{
			foreach($this->Results as $Result)
			{
				$Destination->appendChild($Result->cloneNode(true));
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Prepend content to the inside of every matched element.
	*
	* @param Object $Element The content to prepend.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function prepend(DOMElement $Element)
	{
		$Element = $this->importNode($Element, true);

		foreach($this->Results as $Result)
		{
			if($Result->parentNode->nodeName != '#document')
			{
				$FirstElement = $this->path('*[1]', true, $Result)->seek(0, true);

				if($FirstElement instanceof DOMElement)
				{
					$Result->insertBefore($Element->cloneNode(true), $FirstElement);
				}
				else
				{
					$Result->parentNode->insertBefore($Element->cloneNode(true), $Result);
				}
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Prepend all of the matched elements to another, specified, set of elements.
	*
	* @param String $path_to The destination path of all matched elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function prependTo($path_to)
	{
		foreach($this->path($path_to, true) as $Destination)
		{
			if($Destination->parentNode->nodeName != '#document')
			{
				foreach($this->Results as $Result)
				{
					if($Result->parentNode->nodeName != '#document')
					{
						$FirstElement = $this->path('*[1]', true, $Result)->seek(0, true);

						if($FirstElement instanceof DOMElement)
						{
							$Result->insertBefore($Destination->cloneNode(true), $FirstElement);
						}
						else
						{
							$Result->parentNode->insertBefore($Destination->cloneNode(true), $Result);
						}
					}
				}
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Insert content before each of the matched elements.
	*
	* @param Object $Element The content to insert.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function before(DOMElement $Element)
	{
		$Element = $this->importNode($Element, true);

		foreach($this->Results as $Result)
		{
			if($Result->parentNode->nodeName != '#document')
			{
				$Result->parentNode->insertBefore($Element->cloneNode(true), $Result);
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Insert content after each of the matched elements.
	*
	* @param Object $Element The content to insert.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function after(DOMElement $Element)
	{
		$Element = $this->importNode($Element, true);

		foreach($this->Results as $Result)
		{
			if(false == is_null($Result->parentNode) && ($Result->parentNode->nodeName != '#document' || $Result->nextSibling))
			{
				$Result->parentNode->insertBefore($Element->cloneNode(true), $Result->nextSibling);
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Replaces all matched elements with the value of $Element.
	*
	* @param Object $Element The content to replace the matched elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function replace(DOMElement $Element)
	{
		$Element = $this->importNode($Element, true);

		foreach($this->Results as $Result)
		{
			$Result->parentNode->replaceChild($Element->cloneNode(true), $Result);
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Attempts to retrieve the attribute matching the value of $key from all
	* matched elements.
	*
	* @param String $key   The name of the attribute.
	* @param String $value The value of the attribute.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function getAttr($key)
	{
		$nodes = array();

		foreach($this->Results as $Result)
		{
			$nodes[] = $Result->getAttributeNode($key);
		}

		return $nodes;
	}


   // ! Executor Method

   /**
	* Adds an attribute/value set to all matched elements.
	*
	* @param String $key   The name of the attribute.
	* @param String $value The value of the attribute.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function setAttr($key, $value)
	{
		foreach($this->Results as $Result)
		{
			$Result->setAttribute($key, $value);
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Removes a specified attribute from all matched elements.
	*
	* @param String $key The name of the attribute to remove.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function removeAttr($key)
	{
		foreach($this->Results as $Result)
		{
			if($Result->hasAttributes() && $Result->hasAttribute($key))
			{
				$Result->removeAttribute($key);
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Merges another XML document with the current one. Optionally, it will
	* also replicate the merging document across all matched elements using
	* the given XPath expression.
	*
	* @param String $source     The XML source document.
	* @param String $path_from  XPath expression to locate elements to merge.
	* @param String $path_to    XPath expression denoting the location of mergin elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function merge($source, $path_from, $path_to)
	{
		$Dom = new parent($this->version, $this->encoding);

		if(false == $Dom->loadXml($source))
		{
			throw new DOMException('XML source could not be loaded into the DOM.');
		}

		$XPath = new DOMXPath($Dom);

		foreach($this->path($path_to, true) as $To)
		{
			if(false == in_array($To->nodeName, array('#text', '#document')))
			{
				foreach($XPath->query($path_from) as $From)
				{
					if(false == in_array($To->nodeName, array('#text', '#document')))
					{
						$From = $this->importNode($From, true);

						$To->appendChild($From->cloneNode(true));
					}
				}
			}
		}

		return $this;
	}
}


/***
 * XPathResultIterator
 *
 * An iterator class that compliments XPath query results. This allows us to
 * manipulate multiple result sets at once within DomQuery.
 *
 * @package DomQuery
 * @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @copyright Copyright (c) 2010, Daniel Wilhelm II Murdoch
 * @link http://www.thedrunkenepic.com
 * @version 1.2.0
 ***/
class XPathResultIterator implements Iterator, ArrayAccess, Countable
{
   /**
	* Holds the results of the latest XPath pattern.
	* @access Private
	* @var Object
	*/
	private $DOMNodeList;


   /**
	* The current position of the iterator.
	* @access Private
	* @var Integer
	*/
	private $position;


   // ! Constructor Method

   /**
	* Instantiates class and defines instance variables.
	*
	* @param Object $DOMNodeList The result set of the last XPath query.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Void
	*/
	public function __construct(DOMNodeList &$DOMNodeList)
	{
		$this->DOMNodeList = &$DOMNodeList;
		$this->position    = 0;
	}


   // ! Accessor Method

   /**
	* Returns the current position of the iterator.
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Object
	*/
	public function key()
	{
		return $this->position;
	}


   // ! Accessor Method

   /**
	* Returns the number of results.
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Integer
	*/
	public function count()
	{
		return $this->DOMNodeList->length;
	}


   // ! Executor Method

   /**
	* Moves the internal pointer to the next result.
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Integer
	*/
	public function next()
	{
		return $this->position++;
	}


   // ! Executor Method

   /**
	* Moves the internal pointer to the previous result.
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Integer
	*/
	public function rewind()
	{
		return $this->position = 0;
	}


   // ! Accessor Method

   /**
	* Returns the element assigned to the current pointer.
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Object
	*/
	public function current()
	{
		return $this->DOMNodeList->item($this->position);
	}


   // ! Accessor Method

   /**
	* Used to determine whether the current pointer is valid.
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Boolean
	*/
	public function valid()
	{
		return false == is_null($this->DOMNodeList->item($this->position));
	}


   // ! Executor Method

   /**
	* Moves the pointer to the specified index.
	*
	* @param Integer $index  The new position of the iterator.
	* @param Integer $return Returns the value of the new position.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Boolean
	*/
	public function seek($index, $return = true)
	{
		if($index <= $this->count() && $index >= 0)
		{
			$this->position = $index;

			if($return)
			{
				return $this->current();
			}

			return true;
		}

		return false;
	}

	public function offsetSet($offset, $value)
	{
		return null;
	}

	public function offsetExists($offset)
	{
		return false == is_null($this->DOMNodeList->item($offset));
	}

	public function offsetUnset($offset)
	{
		unset($this->container[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->DOMNodeList->item($offset) : null;
	}
}

/* End of file domquery.php */