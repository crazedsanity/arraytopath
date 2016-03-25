<?php
/*
 * Created on Nov 20, 2006
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 * 
 * 
 * Basically traverses an array as though it were a filesystem. In the given example, it looks 
 * 	more complex than necessary, but the "NEW WAY" is very programatic, whereas the "OLD WAY" is
 * 	just that: OLD.  Also, the new way is very extensible, and is handy when performing a LOT of
 * 	complex operations on an array.
 * Example:
 * 		OLD WAY:
 *	 		$my_data  = $array['path']['to']['your']['hidden']['data'];
 *			$my_vault = $array['path']['to']['your']['hidden']['vault'];
 *			$my_other = $array['path']['to']['your']['hidden']['other'];
 *
 *			$array['path']['to']['my'] = array();
 *			$array['path']['to']['my']['data'] = array();
 *			$array['path']['to']['my']['data']['is'] = "here";
 *	 	NEW WAY:
 *			$arrayToPath = new ArrayToPath($array);
 *			$my_data  = $arrayToPath('/path/to/your/hidden/data');
 *			$my_vault = $arrayToPath('/path/to/your/hidden/vault');
 *			$my_other = $arrayToPath('/path/to/your/hidden/other');
 *
 *			$arrayToPath->set_data('/path/to/my/data/is', 'here');
 */ 	

namespace crazedsanity\arraytopath;
use \Exception;
use \InvalidArgumentException;
use crazedsanity\core\ToolBox;

class ArrayToPath {
	
	private $prefix		= NULL;	//the first directory to use.
	private $data;
	private $iteration = 0;
	private $validPaths=array();
	
	//======================================================================================
	/**
	 * The constructor.
	 * 
	 * @param $array	(array) The data that will be used when 
	 * 
	 * TODO::: there is a strange recursion issue when $prefix is non-null: prefix is presently hardwired as NULL for now... 
	 */
	public function __construct($array=null) {
		if(is_array($array)) {
			$this->data = $array;
		}
		else {
			$this->data = array();
		}
	}//end __construct()
	//======================================================================================
	
	
	//======================================================================================
	/**
	 * Takes a path & returns the appropriate index in the session.
	 * 
	 * @param $path				<str> path to the appropriate section in the session.
	 * 
	 * @return <NULL>			FAIL: unable to find requested index.
	 * @return <mixed>			PASS: this is the value of the index.
	 */
	public function get_data($path=NULL) {
		$myIndexList = array();
		$path = $this->fix_path($path);
		
		if(is_null($path) || (strlen($path) < 1)) {
			//they just want ALL THE DATA.
			$retval = $this->data;
		}
		else {
			//get the list of indices in our data that we have to traverse.
			$myIndexList = $this->explode_path($path);
			
			//set an initial retval.
			$retval = $this->get_data_segment($this->data, $myIndexList[0]); 
			unset($myIndexList[0]);
			
			if(count($myIndexList) > 0) {
				foreach($myIndexList as $indexName) {
					$retval = $this->get_data_segment($retval, $indexName);
					if(is_null($retval)) {
						//hmm... well, if it's null, it's nothing which can have a sub-index.  Stop here.
						break;
					}
				}
			}
		}
		
		return($retval);
		
	}//end get_data()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Returns a given index from a piece of data, used by get_data().
	 */
	private function get_data_segment($fromThis, $indexName) {
		$retval = null;
		if(is_array($fromThis)) {
			//it's an array.
			$retval = null;
			if(isset($fromThis[$indexName])) {
				$retval = $fromThis[$indexName];
			}
		}
		elseif(is_object($fromThis)) {
			throw new InvalidArgumentException("objects are not supported");
		}
		return($retval);
	}//end get_data_segment()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Fixes issues with extra slashes and such.
	 * 
	 * @param $path		<str> path to fix
	 * 
	 * @return <str>	PASS: this is the fixed path
	 */
	public static function fix_path($path) {
		$retval = preg_replace('~/$~', '', ToolBox::resolve_path_with_dots('/'. $path));
		
		return($retval);
		
	}//end fix_path()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Sets data into the given path, with options to override our internal prefix, and to
	 * force-overwrite data if it's not an array.
	 * 
	 * @param $path				<str> path to set the data into.
	 * @param $data				<mixed> what to set into the given path.
	 * 
	 * @return 0				FAIL: old data doesn't match new data.
	 * @return 1				PASS: everything lines-up.
	 */
	public function set_data($path, $data) {
		if(is_object($data)) {
			throw new InvalidArgumentException("objects are not supported");
		}
		else {
			//get the list of indices in the session that we have to traverse.
			$myIndexList = $this->explode_path($path);

			$retval = 0;
			//Use an internal iterator to go through the little bits of the session & set the
			//	data where it's supposed to be.
			if($path === '/' || count($myIndexList) == 0) {
				//setting the data.
				$this->data = $data;
				$retval = 1;
			}
			elseif(count($myIndexList) == 1) {
				//that should be simple: set the index to be $data.
				if(!is_array($this->data)) {
					$this->data = array();
				}
				$this->data[$myIndexList[0]] = $data;
				$retval = 1;
			}
			elseif(count($myIndexList) > 1) {
				$this->internal_iterator($this->data, $path, $data);
				$retval = 1;
			}
		}
		
		return($retval);
	}//end set_data()
	//======================================================================================
	
	
	//======================================================================================
	/**
	 * Iterates through the session to create the values for set_data().  This method passes
	 * AND returns the $array argument by reference.
	 * 
	 * @param &$array		(array) iterate through this.
	 * @param $path			(array) numbered array of keys, representing a path through the
	 * 							internal data to go through to set $data.
	 * @param $data			(mixed) data to set into the path referenced in $indexList.
	 * 
	 * @return <void>
	 */
	protected function internal_iterator(&$array, $path, $data) {
		//make sure it doesn't call itself to death.  ;) 
		$this->iteration++;
		
		if($this->iteration > 1000) {
			throw new exception(__METHOD__ .": too many iterations, path=($path)");
		}
		
		$retval = 0;
		$indexList = $this->explode_path($path);
		$myIndex = array_shift($indexList);
		$path = $this->string_from_array($indexList);
		
		
		if(is_array($array)) {
			if(!strlen($path)) {
				if(isset($myIndex)) {
					// setting the final piece of the array.
					$array[$myIndex] = $data;
				}
				else {
					throw new Exception(__METHOD__ .": no index ($myIndex) to follow at the end of the path");
				}
			}
			else {
				if((count($indexList) == 0) || (is_array($indexList) && count($indexList) > 0)) {
					if(!isset($array[$myIndex]) || !is_array($array[$myIndex])) {
						$array[$myIndex] = array(); 
					}
					$array = &$array[$myIndex];

					$newPath = $path;
					if(count($indexList) == 1) {
						$newPath = $indexList[0];
					}

					$this->internal_iterator($array, $newPath, $data);
				}
				else {
					//not sure what to do but throw an exception.
					throw new exception(__METHOD__ .": unknown error ('not sure what to do'): ($array)");
				}
			}
		}
		else {
			throw new exception(__METHOD__ .": found unknown data type in path ($array)");
		}
		
		//decrement the iteration, so methods using it can call it multiple times without worrying about accidentally hitting the limit.
		$this->iteration--;
	}//end internal_iterator()
	//======================================================================================
	
	
	
	
	//======================================================================================
	/**
	 * Will unset the final index in the $path var.  I.E. to unset $this->array['x']['y'],
	 *	call unset_data('/x/y')
	 * 
	 * @param $path		(str) path to unset data; The last item in the path will be removed.
	 */
	public function unset_data($path) {
		//explode the path.
		$pathArr = $this->explode_path($path);
		$removeThis = array_pop($pathArr);
		$path = $this->string_from_array($pathArr);
		
		//retrieve the data...
		$myData = $this->get_data($path);
		
		if(is_array($myData)) {
			//now remove the bit of data as requested.
			unset($myData[$removeThis]);
			//update the path with our new data.
			$retval = $this->set_data($path, $myData);
		}
		else {
			//throw a terrible error.
			throw new exception(__METHOD__ .": data ($myData) wasn't an array! ($path)");
		}
		
		return($retval);
	}//end unset_data()
	//======================================================================================
	
	
	
	//======================================================================================
	/**
	 * Performs all the work of exploding the path and fixing it.
	 * 
	 * @param $path		<string> Path to work with.
	 * @return <array>	PASS: array contains exploded path.
	 */
	public function explode_path($path) {
		$path = preg_replace('/\/{2,}/', '/', $path);
		$path = $this->fix_path($path);
		$retval = explode('/', $path);
		
		//if the initial index is blank, just remove it.
		if($retval[0] == '' || strlen($retval[0]) < 1) {
			//it was blank!  KILL IT!
			$checkItOut = array_shift($retval);
		}
		
		return($retval);
	}//end explode_path()
	//======================================================================================
	
	
	
	//======================================================================================
	public function reload_data($array) {
		//call the constructor on it, and pass along the CURRENT prefix, so it doesn't get reset.
		$this->__construct($array);
	}//end reload_data()
	//======================================================================================
	
	
	
	//======================================================================================
	private function string_from_array(array $array) {
		$retval = "";
		foreach($array as $index) {
			if(strlen($retval)) {
				$retval .= "/". $index;
			}
			else {
				$retval = $index;
			}
		}
		return($retval);
	}//end string_from_array()
	//======================================================================================
	
	
	
	//======================================================================================
	private function path_tracer() {
		
		//TODO: consider working out how to trace paths one at a time, so the list of valid paths comes back in a more predictable manner.
		
		$tracerData = array();
		$this->validPaths = array();
		//build the initial tracerData for exploring the depths of this beastly array...
		foreach($this->data as $path=>$data) {
			if(is_array($data)) {
				$tracerData[] = array(
					'path'	=> "/". $path,
					'data'	=> $data
				);
			}
			else {
				//initial key ($path) doesn't have an array beneath it, so it's a blank root path.
				$this->validPaths[] = '/'. $path;
			}
		}
		
		//If we have anything left, we'll need to do some crazy while() looping madness.
		$i=0;
		if(is_array($tracerData) && count($tracerData)) {
			while($myData = array_shift($tracerData)) {
				
				if(is_array($myData) && count($myData) == 2 && isset($myData['path'])) {
					if(is_array($myData['data']) && count($myData['data'])) {
						$basePath = $myData['path'];
						foreach($myData['data'] as $key=>$val) {
							$tracerData[] = array(
								'path'	=> $basePath .'/'. $key,
								'data'	=> $val
							);
						}
					}
					else {
						$this->validPaths[] = $myData['path'];
					}
				}
				else {
					throw new exception(__METHOD__ .": invalid data or missing path");
				}
				
				$i++;
				if($i >= 5000) {
					exit(__METHOD__ .": too many loops");
				}
			}
		}
		
		
	}//end path_tracer()
	//======================================================================================
	
	
	
	//======================================================================================
	public function get_valid_paths() {
		$this->path_tracer();
		return($this->validPaths);
	}//end get_valid_paths()
	//======================================================================================
	
}//end arrayToPath{}

