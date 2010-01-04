<?php

// TODO: Allow sub-xpath expressions for all methods
// TODO: Implement exception handling in all methods

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
 * @copyright Copyright (c) 2008, Daniel Wilhelm II Murdoch
 * @link http://www.thedrunkenepic.com
 * @since Build 1.0.0 Alpha
 ***/
class DomQuery extends DOMDocument
{
   /**
	* Holds the results of the latest XPath pattern.
	* @access Public
	* @var Object
	*/
	public $Results;


   /**
	* The type of document currently loaded.
	* @access Public
	* @var String
	*/
	public $type;


   // ! Constructor Method

   /**
	* Instantiates class and defines instance variables.
	*
	* @param String $version The version of the document.
	* @param String $encoding The character encoding of the document.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Void
	*/
	public function __construct($version = '1.0', $encoding = 'utf-8')
	{
		parent::__construct($version, $encoding);

		$this->Results = array();
		$this->type    = 'xml';
	}


   // ! Executor Method

   /**
	* Loads source data into the DOMDocument object.
	* Chainable
	*
	* @param String $source Source data to pass into the DOMDocument object
	* @param String $type The type of source data being passed (xml|html|file)
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function load($source, $type = 'xml')
	{
		// TODO: Make be a bit more elegant and intelligent:

		try
		{
			switch($type)
			{
				default:
				case 'xml':

					$this->loadXml($source);
					$this->type = 'xml';

					break;

				case 'html':

					$this->loadHtml($source);
					$this->type = 'html';

					break;

				case 'file':

					if(false == file_exists($source))
					{
						throw new DomQueryException('The file you requsted could not be found.');
					}

					$this->loadHtmlFile($source);
					$this->type = 'html';

					break;
			}
		}
		catch(DomQueryException $Exception)
		{
			die("<strong>Exception Caught:</strong> " . $Exception->getMessage());
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Saves the currently loaded document with any applied changes.
	* Chainable
	*
	* @param none
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Current Instance
	*/
	public function save()
	{
		// TODO: Implement this functionality
	}


   // ! Executor Method

   /**
	* Applies an XPath query to the current document.
	* Chainable
	*
	* @param String $path xpath query to execute
	* @param Boolean $return Return the result set rather than a self instance?
	* @param Object $Context You may run an xpath query on a specific element.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function path($path, $return = false, DOMNode $Context = null)
	{
		$XPath = new DOMXPath($this);

		if($Context instanceof DOMNode)
		{
			if($return)
			{
				return new XPathResultIterator($XPath->query($path, $Context));
			}

			$this->Results = new XPathResultIterator($XPath->query($path, $Context));
		}
		else
		{
			if($return)
			{
				return new XPathResultIterator($XPath->query($path));
			}

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
	* Chainable
	*
	* @param String | Array $callback The callback method or function to apply result elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function walk($callback)
	{
		try
		{
			$arguments = func_get_args();

			array_shift($arguments);

			for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
			{
				if(is_array($callback) && sizeof($callback) == 2)
				{
					if(false == class_exists($callback[0]))
					{
						throw new DomQueryException('object does not exist');
					}
					else if(false == method_exists($callback[0], $callback[1]))
					{
						throw new DomQueryException('method does not exist');
					}
					else if(false == is_callable($callback))
					{
						throw new DomQueryException('method is not callable');
					}
				}
				else if(false == function_exists($callback))
				{
					throw new DomQueryException('function does not exist');
				}

				array_unshift($arguments, array
				(
					'results'  => &$this->Results,
					'element'  => $this->Results->current(),
					'position' => $this->Results->position(),
					'context'  => &$this
				));

				call_user_func_array($callback, $arguments);

				array_shift($arguments);
			}
		}
		catch(DomQueryException $Exception)
		{
			die("<strong>Exception Caught:</strong> " . $Exception->getMessage());
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
	* Chainable
	*
	* @param String $function Content of the function to execute.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
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

		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			array_unshift($arguments, array
			(
				'results'  => &$this->Results,
				'element'  => $this->Results->current(),
				'position' => $this->Results->position(),
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
	* Chainable
	*
	* @param String $path The destination of the copied elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function replicate($path)
	{
		for($Destination = $this->path($path, true); $Destination->valid(); $Destination->next())
		{
			for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
			{
				$Destination->current()->appendChild($this->Results->current()->cloneNode(true));
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Clears the contents of all matched elements.
	* Chainable
	*
	* @param none
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function clear()
	{
		// TODO: Make sure nested elements are removed as well

		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			$this->Results->current()->nodeValue = '';
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Completely removes all matched elements.
	* Chainable
	*
	* @param none
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function remove()
	{
		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			$this->Results->current()->parentNode->removeChild($this->Results->current());
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Append content to the inside of every matched element.
	* Chainable
	*
	* @param Object $Content The content to append.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function append($Content)
	{
		$this->importNode($Content, true);

		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			$this->Results->current()->appendChild($Content->cloneNode(true));
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Append all of the matched elements to another, specified, set of elements.
	* Chainable
	*
	* @param String $path The destination path of all matched elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function appendTo($path)
	{
		for($Destination = $this->path($path, true); $Destination->valid(); $Destination->next())
		{
			for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
			{
				$Destination->current()->appendChild($this->Results->current()->cloneNode(true));
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Prepend content to the inside of every matched element.
	* Chainable
	*
	* @param Object $Content The content to prepend.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function prepend($Content)
	{
		// TODO: Take normal text or CDATA into account.

		$this->importNode($Content, true);

		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			$FirstElement = $this->path('*[1]', true, $this->Results->current())->item(0);

			if($FirstElement instanceof DOMElement)
			{
				$this->Results->current()->insertBefore($Content->cloneNode(true), $FirstElement);
			}
			else
			{
				$this->Results->current()->parentNode->insertBefore($Content->cloneNode(true), $this->Results->current());
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Prepend all of the matched elements to another, specified, set of elements.
	* Chainable
	*
	* @param String $path The destination path of all matched elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function prependTo($path)
	{
		for($Destination = $this->path($path, true); $Destination->valid(); $Destination->next())
		{
			for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
			{
				$FirstElement = $this->path('*[1]', true, $Destination->current())->item(0);

				if($FirstElement instanceof DOMElement)
				{
					$Destination->current()->insertBefore($this->Results->current()->cloneNode(true), $FirstElement);
				}
				else
				{
					$Destination->current()->parentNode->insertBefore($this->Results->current()->cloneNode(true), $Destination->current());
				}
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Insert content before each of the matched elements.
	* Chainable
	*
	* @param Object $Content The content to insert.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function before($Content)
	{
		$this->importNode($Content, true);

		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			if($this->Results->current()->parentNode->nodeName != '#document')
			{
				$this->Results->current()->parentNode->insertBefore($Content->cloneNode(true), $this->Results->current());
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Insert content after each of the matched elements.
	* Chainable
	*
	* @param Object $Content The content to insert.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function after($Content)
	{
		$this->importNode($Content, true);

		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			if($this->Results->current()->parentNode->nodeName != '#document' && $this->Results->current()->nextSibling)
			{
				$this->Results->current()->parentNode->insertBefore($Content->cloneNode(true), $this->Results->current()->nextSibling);
			}
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Replaces all matched elements with the value of $Content.
	* Chainable
	*
	* @param Object $Content The content to replace the matched elements.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function replace($Content)
	{
		$this->importNode($Content, true);

		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			$this->Results->current()->parentNode->replaceChild($Content->cloneNode(true), $this->Results->current());
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Attempts to retrieve the attribute matching the value of $key from all
	* matched elements.
	* Chainable
	*
	* @param String $key The name of the attribute
	* @param String $value The value of the attribute
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
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
echo '<pre>';
print_r($nodes);
echo '</pre>';
exit();
		return $nodes;
	}


   // ! Executor Method

   /**
	* Adds an attribute/value set to all matched elements.
	* Chainable
	*
	* @param String $key The name of the attribute
	* @param String $value The value of the attribute
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function setAttr($key, $value)
	{
		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			$this->Results->current()->setAttribute($key, $value);
		}

		return $this;
	}


   // ! Executor Method

   /**
	* Removes a specified attribute from all matched elements.
	* Chainable
	*
	* @param String $key The name of the attribute to remove.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.0 Alpha
	* @access Public
	* @return Object
	*/
	public function removeAttr($key)
	{
		for($this->Results->reset(); $this->Results->valid(); $this->Results->next())
		{
			if($this->Results->current()->hasAttributes() && $this->Results->current()->hasAttribute($key))
			{
				$this->Results->current()->removeAttribute($key);
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
 * @copyright Copyright (c) 2008, Daniel Wilhelm II Murdoch
 * @link http://www.thedrunkenepic.com
 * @since Build 1.0.1 Alpha
 ***/
class XPathResultIterator extends ArrayObject
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
	* @param Object $DOMNodeList The result set of the last xpath query.
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Void
	*/
	public function __construct(DOMNodeList &$DOMNodeList)
	{
		parent::__construct($DOMNodeList);

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
	* Resets the internal pointer to the first position.
	*
	* @param None
	* @author Daniel Wilhelm II Murdoch <wilhelm.murdoch@gmail.com>
	* @since Build 1.0.1 Alpha
	* @access Public
	* @return Integer
	*/
	public function reset()
	{
		return $this->position = 0;
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
		return $this->position--;
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


	public function offsetSet($offset, $value)
	{

	}

	public function offsetExists($offset)
	{
		return is_null($this->DOMNodeList->item($offset)) ? false : true;
	}

	public function offsetUnset($offset)
	{

	}

	public function offsetGet($offset)
	{
		return $this->DOMNodeList->item($offset);
	}
}


class DomQueryException extends Exception { }

/* End of file domquery.php */