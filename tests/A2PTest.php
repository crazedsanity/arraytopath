<?php
/*
 * Created on Jan 25, 2009
 */

use crazedsanity\arraytopath\ArrayToPath;
use crazedsanity\core\ToolBox;

class TestOfArrayToPath extends PHPUnit_Framework_TestCase {
	
	//-------------------------------------------------------------------------
	function setUp() {
		$this->a2p = new arraytopath(array());
		$this->gfObj = new ToolBox();
	}//end setUp()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_basics() {
		//make sure nothing is in the object initialially.
		$this->assertEquals(array(), $this->a2p->get_data());
		
		$newData = array(
			'look at me'	=> '23dasdvcv3q3qeedasd'
		);
		$this->a2p->reload_data($newData);
		$this->assertNotEquals(array(), $this->a2p->get_data());
		
		
		//load a complex array & test to ensure the returned value is the same.
		$newData = array(
			'x'		=> array(
				'y'		=> array(
					'z'		=> array(
						'fiNal'		=> 'asdfadsfadfadsfasdf'
					)
				),
				'_y_'	=> null,
				'-'		=> null
			),
			'a nother path2 Stuff -+=~!@#$' => '-x-'
		);
		$this->a2p->reload_data($newData);
		$this->assertEquals($newData, $this->a2p->get_data());
		$this->assertEquals($newData['x']['y']['z']['fiNal'], $this->a2p->get_data('/x/y/z/fiNal'));
		
		//before going on, test that the list of valid paths makes sense.
		$expectedValidPaths = array(
			'/x/y/z/fiNal',
			'/a nother path2 Stuff -+=~!@#$',
			'/x/_y_',
			'/x/-',
		);
		$actualValidPaths = $this->a2p->get_valid_paths();
		$this->assertEquals(count($expectedValidPaths), count($actualValidPaths));
		
		//NOTE: since cs_arrayToPath::get_valid_paths() doesn't return paths in their found order, can't directly compare the arrays.
		$this->assertEquals(count($expectedValidPaths), count($actualValidPaths)); 
		foreach($expectedValidPaths as $i=>$path) {
			$findIndex = array_search($path, $actualValidPaths);
			$this->assertTrue(is_numeric($findIndex));
			$this->assertTrue(strlen($expectedValidPaths[$findIndex])>0);
			$this->assertTrue(strlen($actualValidPaths[$findIndex])>0);
		}
		
		
		$this->a2p->set_data('/x/y/z/fiNal', null);
		$this->assertNotEquals($this->a2p->get_data('/x/y/z/fiNal'), $newData['x']['y']['z']['fiNal']);
		
		//ensure paths with dots are ok.
		$this->assertEquals($this->a2p->get_data('/x/y/z/fiNal'), $this->a2p->get_data('/x/y/z/g/q/x/../../../fiNal'));
		
		//make sure extra slashes are okay.
		$this->assertEquals($this->a2p->get_data('/x/y/z/fiNal'), $this->a2p->get_data('/x/y//z///fiNal//'));
	}//end test_basics()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_path_tracer() {
		
		$myTests = array(
			'simple' => array(
					'data' => array(
						'x'=>null,
						'y'=>null
					),
					'paths' => array(
						'/x',
						'/y'
					)
			),
			'moreComplex' => array(
					'data' => array(
						'x' => array(
							'y' => array(
							)
						),
						'x2' => array(
							'y' => array(
							)
						)
					),
					'paths' => array(
						'/x/y',
						'/x2/y'
					)
			),
			'numericData' => array(
					'data' => array(
						0	=> array(
							1 => array()
						),
						'1'	=> array(
							'1' => array()
						),
						2	=> array(
							'0'
						)
					),
					'paths' => array(
						'/0/1',
						'/1/1',
						'/2/0'
					)
			),
			'dataWithDepth' => array(
					'data' => array(
						'1' => array(
							'2' => array(
								'3' => array(
									'4' => ""
								)
							)
						),
						'one' => array(
							'two' => array(
								'three' => array(
									'checkme' => array(),
									'four' => array()
								)
							)
						),
						'first' => array(
							'second' => array(
								'third' => array(
									'fourth' => array(
										'fifth' => array(
											'sixth' => array(
												'seventh' => array()
											)
										)
									)
								)
							)
						)
					),
					'paths' => array(
						'/1/2/3/4',
						'/one/two/three/checkme',
						'/one/two/three/four',
						'/first/second/third/fourth/fifth/sixth/seventh'
					)
			),
			'likeXML' => array(
					'data'	=> array(
						'methodResponse' => array(
							'methodName'	=> 'blogger.getUsersBlogs',
							'info' => array(
								'deeper'	=> array(
									'test' => array(
										'of' => array(
											'path' => array(
												'tracer' => 'YEAH!'
											)
										)
									)
								)
							),
							'params' => array(
								'param' => array(
									array(
										'value'	=> array(
											'string'	=> null
										)
									),
									array(
										'value' => array(
											'string'	=> 'usernameHere'
										)
									),
									array(
										'value' => array(
											'string'	=> 'passw0rd'
										)
									) 
								)
							)
						),
					
					),
					'paths' => array(
						'/methodResponse/methodName',
						'/methodResponse/info/deeper/test/of/path/tracer',
						'/methodResponse/params/param/0/value/string',
						'/methodResponse/params/param/1/value/string',
						'/methodResponse/params/param/2/value/string'
					)
			),
		);
		
		
		foreach($myTests as $testName=>$testData) {
			$this->a2p->reload_data($testData['data']);
			
			$validPaths = $this->a2p->get_valid_paths();
			if(!$this->assertEquals(count($testData['paths']), count($validPaths))) {
				$this->gfObj->debug_print(__METHOD__ .": failed test (". $testName .")... VALID PATHS::: ". $this->gfObj->debug_print($validPaths,0,1) .
						", EXPECTED PATHS::: ". $this->gfObj->debug_print($testData['paths'],0,1));
			}
			
			foreach($testData['paths'] as $path) {
				$index = array_search($path, $validPaths);
				$this->assertTrue(strlen($testData['paths'][$index])>0);
			}
		}
		
	}//end test_path_tracer()
	//-------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------
	function test_pathWithDots() {
		$data = array(
			'x'	=> array(
				'y'	=> array(
					'z'	=> __METHOD__
				)
			)
		);
		
		$a2p = new ArrayToPath($data);
		$this->assertEquals(__METHOD__, $a2p->get_data('/x/y/z'));
		$this->assertEquals(__METHOD__, $a2p->get_data('/x/./y/z'));
		$this->assertEquals(__METHOD__, $a2p->get_data('/x/../x/y/z'));
		$this->assertEquals(__METHOD__, $a2p->get_data('/x/.././../../x/y/z'));
	}
	//-------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------
	function test_badLastIndex() {
		$data = array(
			'test'	=> array(
				'one'	=> array(
					'two'	=> array(
						
					)
				)
			)
		);
		$a2p = new ArrayToPath($data);
		$a2p->get_data('/test/one/two/three/four');
	}
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_createWithoutArray() {
		$a2p = new ArrayToPath();
		$this->assertEquals(array(), $a2p->get_data('/'));
	}
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_set_data() {
		$a2p = new ArrayToPath();
		$this->assertEquals(1, $a2p->set_data('/path/to/somewhere/good', __METHOD__));
		$this->assertEquals(__METHOD__, $a2p->get_data('/path/to/somewhere/good'));
		
		$this->assertEquals(1, $a2p->set_data('/', __METHOD__));
		$this->assertEquals(__METHOD__, $a2p->get_data('/'));
		
		$this->assertEquals(1, $a2p->set_data('/test', __METHOD__));
		$this->assertEquals(__METHOD__, $a2p->get_data('/test'));
	}
	//-------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------
	/**
	 * @expectedException InvalidArgumentException
	 */
	function test_setObject_rootlevel() {
		$a2p = new ArrayToPath();
		$a2p->set_data('/', new stdClass());
	}
	//-------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------
	/**
	 * @expectedException InvalidArgumentException
	 */
	function test_setObject_oneDeep() {
		$a2p = new ArrayToPath();
		$a2p->set_data('/one', new stdClass());
	}
	//-------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------
	/**
	 * @expectedException InvalidArgumentException
	 */
	function test_setObject_twoDeep() {
		$a2p = new ArrayToPath();
		$a2p->set_data('/one/two', new stdClass());
	}
	//-------------------------------------------------------------------------
	
	
	
	
	//-------------------------------------------------------------------------
	/**
	 * @expectedException InvalidArgumentException
	 */
	function test_objectSupport() {
		$myData = array(
			'one'	=> new stdClass(),
		);
		$myData['one']->two = "three";
		$a2p = new ArrayToPath($myData);
		$a2p->get_data('/one/two/three');
	}
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_fix_path() {
		$a2p = new ArrayToPath();
		$thePath = '//one///////../one/./_two-/.//';
		$this->assertEquals('/one/_two-', ArrayToPath::fix_path($thePath));
		
		$newPath = 'fake/../real/one/two/three';
		$this->assertEquals('/real/one/two/three', ArrayToPath::fix_path($newPath));
	}
	//-------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------
	function test_invalid_path() {
		$a2p = new ArrayToPath();
		
		$this->assertEquals(null, $a2p->get_data('/this/does/not/exist'));
	}
	//-------------------------------------------------------------------------
	
	
	
	
	//-------------------------------------------------------------------------
	function test_get_valid_paths() {
		$empty = new ArrayToPath();
		$this->assertEquals(array(), $empty->get_valid_paths());
		
		$data = array(
			'test' => array(
				'one' => array(
					'two' => array(
						'three' => array(
							
						),
						'another' => array(
							
						)
					)
				),
				'here'	=> array(),
			)
		);
		$matchThis = array(
			'/test/here',
			'/test/one/two/three',
			'/test/one/two/another',
		);
		$a2p = new ArrayToPath($data);
		$this->assertEquals($matchThis, $a2p->get_valid_paths());
	}
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	/**
	 * @expectedException Exception
	 */
	function test_unset_data_not_array_exception() {
		$data = array(
			'one'	=> array(),
			'two'	=> array(),
			'three'	=> __METHOD__
		);
		$a2p = new ArrayToPath();
		$a2p->unset_data('/three/'. __METHOD__);
	}
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_unset_data() {
		$data = array(
			'one'	=> array(),
			'two'	=> array(),
			'three'	=> __METHOD__
		);
		$a2p = new ArrayToPath($data);
		$this->assertEquals($data, $a2p->get_data());
		
		$this->assertEquals(1, $a2p->unset_data('/three'));
		$this->assertNotEquals($data, $a2p->get_data());
		unset($data['three']);
		$this->assertEquals($data, $a2p->get_data());
	}
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_set_value_beneath_text_value() {
		
		$data = array(
			'one'	=> array(),
			'two'	=> array(),
			'three'	=> __METHOD__
		);
		$a2p = new ArrayToPath($data);
		
		$this->assertEquals($data, $a2p->get_data());
		
		$data['three'] = array(
			__METHOD__ => "new"
		);
		$this->assertEquals(1, $a2p->set_data('/three/'. __METHOD__, "new"));
		$this->assertEquals($data, $a2p->get_data());
		
		$data['three'][__METHOD__] = array(
			'new'	=> 'again'
		);
		$this->assertEquals(1, $a2p->set_data('/three/'. __METHOD__ ."/new", 'again'));
		$this->assertEquals($data, $a2p->get_data());
		$this->assertEquals($data['three'][__METHOD__]['new'], $a2p->get_data('/three/'. __METHOD__ .'/new'));
	}
	//-------------------------------------------------------------------------
	
	
}//end testOfA2P{}

