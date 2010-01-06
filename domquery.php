<?php

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
class DomQuery extends DOMDocument
{
   /**
	* Holds the results of the latest XPath pattern.
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
	* Loads source data into the DOMDocument object.
	*
	* @param String $source Source data to pass into the DOMDocument object.
	* @param String $path   An XPath expression may be immediately executed after loading XML.
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
	* Applies an XPath query to the current document.
	*
	* @param String  $path    XPath query to execute.
	* @param Boolean $return  Return the result set rather than a self instance.
	* @param Object  $Context You may run an xpath query on a specific element.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function path($path, $return = false, DOMNode $Context = null)
	{
		$XPath = new DOMXPath($this);

		if($Context instanceof DOMNode)
		{
			$this->Results = new XPathResultIterator($XPath->query($path, $Context));
		}
		else
		{
			$this->Results = new XPathResultIterator($XPath->query($path));
		}

		if($return)
		{
			return $this->Results;
		}

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
	* Function:
	*
	* walk('function_to_execute');
	*
	* Method:
	*
	* walk(array('class_name', 'method_to_execute'));
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
	* For example:
	*
	* $Xml->load($output)
	*     ->path('//items/*')
	*     ->each($lambda, 'foo', 'bar');
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
	* Copies the elements of the last xpath result to all results of $path.
	*
	* @param String $path The destination of the copied elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function copy($path)
	{
		foreach($this->page($path, true) as $Destination)
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
			$FirstElement = $this->path('*[1]', true, $Result)->item(0);

			if($FirstElement instanceof DOMElement)
			{
				$Result->insertBefore($Element->cloneNode(true), $FirstElement);
			}
			else
			{
				$Result->parentNode->insertBefore($Element->cloneNode(true), $Result);
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Prepend all of the matched elements to another, specified, set of elements.
	*
	* @param String $path The destination path of all matched elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function prependTo($path)
	{
		foreach($this->page($path, true) as $Destination)
		{
			foreach($this->Results as $Result)
			{
				$FirstElement = $this->path('*[1]', true, $Destination)->item(0);

				if($FirstElement instanceof DOMElement)
				{
					$Destination->insertBefore($Result->cloneNode(true), $FirstElement);
				}
				else
				{
					$Destination->parentNode->insertBefore($Result->cloneNode(true), $Destination);
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
			if($Result->parentNode->nodeName != '#docment' && $Result->nextSibling)
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
	* @param String $source      The XML source document.
	* @param String $path_origin XPath expression to locate elements to merge.
	* @param String $path_destination The XML source document
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @access Public
	* @return Object
	*/
	public function merge($source, $path_origin, $path_destination)
	{
		$Dom = new self;

		if(false == $Dom->loadXml($source))
		{
			throw new DOMException('XML source could not be loaded into the DOM.');
		}

		$XPath = new DOMXPath($Dom);

		foreach($this->path($path_destination, true) as $Destination)
		{
			if(false == in_array($Destination->nodeName, array('#text', '#document')))
			{
				foreach($XPath->query($path_origin) as $Origin)
				{
					if(false == in_array($Destination->nodeName, array('#text', '#document')))
					{
						$Origin = $this->importNode($Origin, true);

						$Destination->appendChild($Origin->cloneNode(true));
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
class XPathResultIterator implements Iterator, Countable
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
	* @param Integer $index The new position of the iterator.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Boolean
	*/
	public function seek($index)
	{
		if($index <= $this->count() && $index >= 0)
		{
			$this->position = $index;

			return true;
		}

		return false;
	}
}

/* End of file domquery.php */