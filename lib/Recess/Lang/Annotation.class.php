<?php
namespace recess\lang;

/**
 * Base class for class, method, and property Recess Annotations.
 * New Annotations can be introduced by extending this abstract class.
 * 
 * @author Kris Jordan <krisjordan@gmail.com>
 * @copyright 2008, 2009 Kris Jordan
 * @package Recess PHP Framework
 * @license MIT
 * @link http://www.recessframework.org/
 */
abstract class Annotation {
	
	protected $errors = array();
	protected $values = array();
	
	public $parameters = array();
	
	static protected $registeredAnnotations = array();
	
	const FOR_CLASS = 1;
	const FOR_METHOD = 2;
	const FOR_PROPERTY = 4;
	
	/* Begin abstract methods */
	
	/**
	 * Returns a string representation of the intended usage of an annotation.
	 * 
	 * @return string
	 */
	abstract public function usage();
	
	/**
	 * Returns an integer representation of the type(s) of PHP language constructs
	 * the annotation is applicable to. Use the Annotation::FOR_* consts to return
	 * the desired result.
	 * 
	 * Examples:
	 *  // Only valid on classes
	 *  function isFor() { return Annotation::FOR_CLASS; }
	 *  
	 *  // Valid on methods or properties
	 *  function isFor() { return Annotation::FOR_METHOD | Annotation::FOR_PROPERTY; }
	 * 
	 * @return integer
	 */
	abstract public function isFor();
	
	/**
	 * Validate is called just before expansion. Because there may be multiple 
	 * constraints of an annotation the implementation of validate should append
	 * any error messages to the protected $errors property. Commonly used validations
	 * helper methods are provided as protected methods on the Annotation class.
	 * 
	 * @param $class The classname the annotation is on.
	 */
	abstract public function validate($class);
	
	/**
	 * Return any errors that occurred during the validation of an annotation.
	 * 
	 * @return array
	 */
	function getErrors() {
		return $this->errors;
	}
	
	/**
	 * The expansion step of an annotation gives it an opportunity to manipulate
	 * a class' descriptor by introducing additional metadata, attach methods, and
	 * wrap methods.
	 * 
	 * @param string $class Classname the annotation is applied to.
	 * @param mixed $reflection The Reflection(Class|Method|Property) object the annotation is applied to.
	 * @param ClassDescriptor $descriptor The ClassDescriptor being manipulated.
	 */
	abstract protected function expand($class, $reflection, $descriptor);
	
	/* End abstract methods */
	
	/* Begin validation helper methods */
	
	/**
	 * Assert the keys which are acceptable for the annotation's values.
	 * Keys are always converted to lower case.
	 * If any keys are used other than these keys an error is added to the
	 * errors array. 
	 * 
	 * @param array $keys
	 */
	protected function acceptedKeys($keys) {
		$keys = array_map('strtolower',$keys);
		foreach($this->parameters as $key => $value) {
			if (is_string($key) && !in_array($key, $keys)) {
				$this->errors[] = "Invalid parameter: \"$key\".";
			}
		}
	}
	
	/**
	 * Assert keys that are required. Keys are always converted to lower case.
	 * If an annotation lacks a required key an error is added to the errors array. 
	 * 
	 * @param array $keys
	 */
	protected function requiredKeys($keys) {
		$keys = array_map('strtolower',$keys);
		foreach($keys as $key) {
			if(!array_key_exists($key, $this->parameters)) {
				$this->errors[] = get_class($this) . " requires a '$key' parameter.";
			}
		}
	}
	
	/**
	 * Assert acceptable values without keys. The case of values are not converted
	 * to lowercase like keys are. If a keyless value is not in the provided array
	 * an error is added to the errors array. 
	 * 
	 * @param array $values
	 */
	protected function acceptedKeylessValues($values) {
		foreach($this->parameters as $key => $value) {
			if(!is_string($key) && !in_array($value, $values)) {
				$this->errors[] = "Unknown parameter: \"$value\".";
			}
		}
	}
	
	/**
	 * Assert acceptable values for a specific indexed element in the values array.
	 * For example, in the !Route annotation the first value must be an HTTP method.
	 * Will add an error to the errors arrary if the indexed value is not in the values
	 * array.
	 * 
	 * @param int $index
	 * @param array $values
	 */
	protected function acceptedIndexedValues($index, $values) {
		if(!isset($this->parameters[$index]) || !in_array($this->parameters[$index],$values)) {
			$this->errors[] = "Parameter $index is set to \"" . $this->parameters[$index] . "\". Valid values: " . implode(', ', $values) . '.';
		}
	}
	
	/**
	 * Assert acceptable values for a keyed parameter. Can also optionally specify a case
	 * constant (CASE_LOWER || CASE_UPPER) that will force the in-code value to 
	 * become uppercase or lowercase for comparison to the provided $values array.
	 * Key, as always, is forced lowercase. Will add an error to errors array
	 * if value provided is not in values array.
	 * 
	 * @param string $key
	 * @param array $values
	 * @param int $case CASE_LOWER or CASE_UPPER, optional
	 */
	protected function acceptedValuesForKey($key, $values, $case = false) {
		$key = strtolower($key);
		
		if(!isset($this->parameters[$key])) { return; }
		
		if($case === false) {
			$value = $this->parameters[$key];
		} else if($case === CASE_LOWER) {
			$value = strtolower($this->parameters[$key]);
		} else if($case === CASE_UPPER) {
			$value = strtoupper($this->parameters[$key]);
		}
		
		if(!in_array($value, $values)) {
			$this->errors[] = 'The "' . $key . '" parameter is set to "' . $this->parameters[$key] . '". Valid values: ' . implode(', ', $values) . '.';
		}
	}
	
	/**
	 * Assert that an annotation only accepts keyed values.
	 */
	protected function acceptsNoKeylessValues() {
		$this->acceptedKeylessValues(array());
	}
	
	/**
	 * Assert that an annotation only accepts keyless values.
	 */
	protected function acceptsNoKeyedValues() {
		$this->acceptedKeys(array());
	}
	
	/**
	 * Assert that an annotation is only valid on a certain portion of the
	 * class hierarchy.
	 * 
	 * @param string $annotatedClass 
	 * @param string $baseClass
	 */
	protected function validOnSubclassesOf($annotatedClass, $baseClass) {
		if( !is_subclass_of($annotatedClass, $baseClass) ) {
			$this->errors[] = get_class($this) . " is only valid on objects of type $baseClass.";
		}
	}
	
	/**
	 * Assert that an annotation requires at least N parameters.
	 * 
	 * @param int $count
	 */
	protected function minimumParameterCount($count) {
		if( ! (count($this->parameters) >= $count) ) {
			$this->errors[] = get_class($this) . " takes at least $count parameters.";
		}
	}
	
	/**
	 * Assert that an annotation can have at most N parameters.
	 * 
	 * @param int $count
	 */
	protected function maximumParameterCount($count) {
		if( ! (count($this->parameters) <= $count) ) {
			$this->errors[] = get_class($this) . " takes at most $count parameters.";
		}
	}
	
	/**
	 * Assert that an annotation requires exactly N parameters.
	 * 
	 * @param int $count
	 */
	protected function exactParameterCount($count) {
		if ( count($this->parameters) != $count ) {
			$this->errors[] = get_class($this) . " requires exactly $count parameters.";
		}
	}
	/* End validation helper methods */
	
	/**
	 * Is a value in the array of values?
	 * 
	 * @param varies $value
	 * @return bool
	 */
	function isAValue($value) {
		return in_array($value, array_merge($this->parameters, $this->values));
	}
	
	/**
	 * Mask other values to return the first not contained in the array.
	 * Example use: Column annotation uses this to find the type as distinct from 
	 * 	other static modifiers (PrimaryKey and AutoIncrement)
	 * 
	 * @param array $values
	 * @return value not in the array of other values
	 */
	function valueNotIn($values) {
		foreach(array_merge($this->parameters, $this->values) as $parameter) {
			if(!in_array($parameter, $values)) {
				return $parameter;
			}
		}
		return false;
	}
	
	/**
	 * The strategy for realizing a Recess Annotation is driven
	 * by this method. The user experience of diagnostics is defined
	 * here, as well, by checking the correctness of an Annotation
	 * and giving annotation implementors a mechanism for providing 
	 * feedback on the correct usage of an annotation.
	 * 
	 * @param string $class The classname this annotation appears on.
	 * @param varies $reflection The Reflection object the annotation corresponds to.
	 * @param ClassDescriptor $descriptor the class descriptor.
	 * @return ClassDescriptor 
	 */
	function expandAnnotation($class, $reflection, $descriptor) {		
		// First check to ensure this annotation is allowed
		// to apply to this type of PHP construct (class, method, property)
		// using a simple bitwise mask.
		if($reflection instanceof ReflectionClass) {
			$annotationIsOn = self::FOR_CLASS;
			$annotationIsOnType = 'class';
		} else if ($reflection instanceof ReflectionMethod) {
			$annotationIsOn = self::FOR_METHOD;
			$annotationIsOnType = 'method';
		} else if ($reflection instanceof ReflectionProperty) {
			$annotationIsOn = self::FOR_PROPERTY;
			$annotationIsOnType = 'property';
		}
		if(!($annotationIsOn & $this->isFor())) {
			$isFor = array();
			foreach(array('Classes' => self::FOR_CLASS, 'Methods' => self::FOR_METHOD, 'Properties' => self::FOR_PROPERTY) as $key => $mask) {
				if($mask & $this->isFor()) {
					$isFor[] = $key; 
				}
			}
			$this->errors[] = get_class($this) . ' is only valid on ' . implode(', ', $isFor) . '.';
			$typeError = true;
		} else {
			$typeError = false;
		}
		
		// Run annotation specified validations
		$this->validate($class);
		
		// Throw Exception if Annotation Errors Exist
		if(!empty($this->errors)) {
			if($reflection instanceof \ReflectionProperty) {
				$message = 'Invalid ' . get_class($this) . ' on property "' . $reflection->getName() . '". ';
				$reflection = new \ReflectionClass($class);
			} else {
				$message = 'Invalid ' . get_class($this) . ' on ' . $annotationIsOnType . ' "' . $reflection->getName() . '". ';
			}
			if(!$typeError) {
				$message .= "Expected usage: \n" . $this->usage();
			}
			$message .= "\n == Errors == \n * ";
			$message .= implode("\n * ", $this->errors);
			throw new \ErrorException($message,0,0,$reflection->getFileName(),$reflection->getStartLine());
		}
		
		// Map keyed parameters to properties on this annotation
		// Place unkeyed parameters on the $this->values array
		foreach($this->parameters as $key => $value) {
			if(is_string($key)) {
				$this->{$key} = $value;
			} else {
				$this->values[] = $value;
			}
		}
		
		// At this point we've processed the parameters, clearing memory
		unset($this->parameters);
		
		// Finally dispatch to abstract method expand() so that
		// Annotation developers can implement glorious new
		// functionalities.
		$this->expand($class, $reflection, $descriptor);
		
		return $descriptor;
	}
	
	/**
	 * Initialize the parameters of the annotation by lowering key case
	 * @param $parameters
	 */
	function init($parameters) {
		$this->parameters = array_change_key_case($parameters, CASE_LOWER);
	}
	
	/**
	 * To register an annotation this static method must be called.
	 * This allows annotations to use a flat namespace even when the
	 * annotations themselves are distributed across many different
	 * namespaces.
	 */
	static public function load() {
		$fullClass = get_called_class();
		$class = explode('\\',$fullClass);
		self::$registeredAnnotations[end($class)] = $fullClass; 
	}
	
	/**
	 * Given a docstring, returns an array of Recess Annotations. Throws
	 * an exception if the docstring cannot be parsed or if an annotation
	 * has not been loaded yet.
	 * 
	 * @param $docstring String.
	 * @return array of Annotations
	 */
	static function parse($docstring) {
		preg_match_all('%(?:\s|\*)*!(\S+)[^\n\r\S]*(?:(.*?)(?:\*/)|(.*))%', $docstring, $result, PREG_PATTERN_ORDER);
		
		$annotations = $result[1];
		if(isset($result[2][0]) && $result[2][0] != '') {
			$values = $result[2];
		} else { 
			$values = $result[3];
		}
		$returns = array();
		if(empty($result[1])) return array();
		foreach($annotations as $key => $annotation) {
			// Strip Whitespace
			$value = preg_replace('/\s*(\(|:|,|\))[^\n\r\S]*/', '${1}', '(' . $values[$key] . ')');
			// Extract Strings
			preg_match_all('/\'(.*?)(?<!\\\\)\'|"(.*?)(?<!\\\\)"/', $value, $result, PREG_PATTERN_ORDER);
			$quoted_strings = $result[2];
			$value = preg_replace('/\'.*?(?<!\\\\)\'|".*?(?<!\\\\)"/', '%s', $value);
			// Insert Single Quotes
			$value = preg_replace('/((?!\(|,|:))(?!\))(.*?)((?=\)|,|:))/', '${1}\'${2}\'${3}', $value);
			// Array Keyword
			$value = str_replace('(','array(',$value);
			// Arrows
			$value = str_replace(':', '=>', $value);
			
			$value = vsprintf($value . ';', $quoted_strings);
			
			@eval('$array = ' . $value);
			if(!isset($array)) { 
				throw new \Exception('There is an unparseable annotation value: "!' . $annotation . ': ' . $values[$key] . '"');
			}
			
			$annotationClass = $annotation . 'Annotation';
			if(isset(self::$registeredAnnotations[$annotationClass])) {
				$annotation = new self::$registeredAnnotations[$annotationClass];
				$annotation->init($array);
			} else {
				throw new \Exception('Unknown annotation: "' . $annotation . '" It must be loaded with: "' . $annotation .'"::load()');
			}
			
			$returns[] = $annotation;
		}
		unset($annotations,$values,$result);
		return $returns;
	}
}