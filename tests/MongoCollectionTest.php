<?php
/**
 * Test class for MongoCollection.
 * Generated by PHPUnit on 2009-04-10 at 13:30:28.
 */
class MongoCollectionTest extends PHPUnit_Framework_TestCase
{
		/**
		 * @var		MongoCollection
		 * @access protected
		 */
		protected $object;

		/**
		 * Sets up the fixture, for example, opens a network connection.
		 * This method is called before a test is executed.
		 *
		 * @access protected
		 */
		protected function setUp()
		{
				$m = new Mongo();
				$db = new MongoDB($m, "phpunit");
				$this->object = $db->selectCollection('c');
				$this->object->drop();
		}

	public function errorHandler($code, $message)
	{
		throw new Exception("HANDLED: $message", $code);
	}

		public function test__toString() {
				if (preg_match("/5\.1\../", phpversion())) {
						$this->markTestSkipped("No implicit __toString in 5.1");
						return;
				}

				$this->assertEquals((string)$this->object, 'phpunit.c');
		}

		public function testGetName() {
				$this->assertEquals($this->object->getName(), 'c');
		}

		public function testDrop() {
				$ns = $this->object->db->selectCollection('system.namespaces');

				$this->object->insert(array('x' => 1));
				$this->object->ensureIndex('x');

				$c = $ns->findOne(array('name' => 'phpunit.c'));
				$this->assertNotNull($c);

				$this->object->drop();

				$c = $ns->findOne(array('name' => 'phpunit.c'));
				$this->assertEquals(null, $c);
		}

		public function testValidate() {
			$v = $this->object->validate();
			$this->assertEquals((bool)$v['ok'], false);
			$this->assertEquals($v['errmsg'], 'ns not found');

			$this->object->insert(array('a' => 'foo'));
			$v = $this->object->validate();
			$this->assertEquals((bool)$v['ok'], true);
			$this->assertEquals($v['ns'], 'phpunit.c');
		}

		public function testInsert() {
			$a = array("n" => NULL,
								 "l" => 234234124,
								 "d" => 23.23451452,
								 "b" => true,
								 "a" => array("foo"=>"bar",
															"n" => NULL,
															"x" => new MongoId("49b6d9fb17330414a0c63102")),
								 "d2" => new MongoDate(1271079861),
								 "regex" => new MongoRegex("/xtz/g"),
								 "_id" => new MongoId("49b6d9fb17330414a0c63101"),
								 "string" => "string");

			$this->assertTrue($this->object->insert($a));
			$obj = $this->object->findOne();

			$this->assertEquals($obj['n'], null);
			$this->assertEquals($obj['l'], 234234124);
			$this->assertEquals($obj['d'], 23.23451452);
			$this->assertEquals($obj['b'], true);
			$this->assertEquals($obj['a']['foo'], 'bar');
			$this->assertEquals($obj['a']['n'], null);
			$this->assertNotNull($obj['a']['x']);
			$this->assertEquals($obj['d2']->sec, 1271079861);
			$this->assertEquals($obj['d2']->usec, 0);
			$this->assertEquals($obj['regex']->regex, 'xtz');
			$this->assertEquals($obj['regex']->flags, 'g');
			$this->assertNotNull($obj['_id']);
			$this->assertEquals($obj['string'], 'string');

			$this->assertTrue($this->object->insert(array(1,2,3,4,5)));
		}


		/**
		 * @expectedException MongoException
		 */
		public function testInsertMsg() {
			$this->object->insert(array());
		}

		public function testInsert2() {
			$this->assertTrue($this->object->insert(array(0)));
			$this->assertTrue($this->object->insert(array(0=>"1")));

			$this->assertEquals($this->object->count(), 2);
			$cursor = $this->object->find();

			$x = $cursor->getNext();
			$this->assertTrue(array_key_exists('0', $x), json_encode($x));

			$x = $cursor->getNext();
			$this->assertTrue(array_key_exists('0', $x));
			$this->assertEquals($x['0'], '1');
		}

		public function testSafeInsert3() {
			$response = $this->object->insert(array("_id" => 1), array("safe" => true));
			$this->assertEquals(true, (bool)$response['ok'], json_encode($response));
			$this->assertNull($response['err']);

			$response = $this->object->insert(array("_id" => 1), array());
			$this->assertTrue($response);
		}

		/**
		 * @expectedException MongoCursorException
		 */
		public function testSafeInsert4() {
			$this->object->insert(array("_id" => 1), array("safe" => true));
			$this->object->insert(array("_id" => 1), array("safe" => true));
		}

		public function testInsertNonAssoc() {
				if (preg_match("/5\.1\../", phpversion())) {
						$this->markTestSkipped("No implicit __toString in 5.1");
						return;
				}

				$nonassoc = array("x", "y", "z");
				$this->object->insert($nonassoc);
				$x = $this->object->findOne();

				$this->assertEquals("x", $x['0']);
				$this->assertEquals("y", $x['1']);
				$this->assertEquals("z", $x['2']);
				$this->assertEquals((string)$nonassoc['_id'], (string)$x['_id']);
		}


		/**
		 * @expectedException MongoException
		 */
		public function testBigInsert() {
				if (preg_match("/5\.1\../", phpversion())) {
						$this->markTestSkipped("bad file handling in 5.1");
						return;
				}

				$x = array("files" => array());
				$contents = file_get_contents('tests/Formelsamling.pdf');

				for ($i=0; $i<20; $i++) {
					$x['files'][] = $contents;
				}

				$this->object->insert($x);
		}

		/**
		 * @expectedException MongoException
		 */
		public function testNoBatch1() {
			$this->object->batchInsert(array());
		}

		/**
		 * @expectedException MongoException
		 */
		public function testNoBatch2() {
			$this->object->batchInsert(array(1,2,3));
		}

		public function testBatchInsert() {
			$this->assertTrue($this->object->batchInsert(array('z'=>array('foo'=>'bar'))));

			$a = array( array( "x" => "y"), array( "x"=> "z"), array("x"=>"foo"));
			$this->object->batchInsert($a);
			$this->assertEquals(4, $this->object->count());

			$cursor = $this->object->find(array("x"=>array('$exists' => 1)))->sort(array("x" => -1));
			$x = $cursor->getNext();
			$this->assertEquals('z', $x['x']);
			$x = $cursor->getNext();
			$this->assertEquals('y', $x['x']);
			$x = $cursor->getNext();
			$this->assertEquals('foo', $x['x']);
		}

		/**
		 * @expectedException MongoException
		 */
		public function testSafeBatchInsert() {
			$this->object->batchInsert(array(array("_id" => 1), array("_id" => 1)), array("safe" => true));
		}

		public function testSafeBatchInsert2() {
			$result = $this->object->batchInsert(array(array("_id" => 1), array("_id" => 1)));
			$this->assertTrue($result);
		}

		public function testFind() {
			for ($i=0;$i<50;$i++) {
				$this->object->insert(array('x' => $i));
			}

			$c = $this->object->find();
			$this->assertEquals(iterator_count($c), 50);
			$c = $this->object->find(array());
			$this->assertEquals(iterator_count($c), 50);

			$this->object->insert(array("foo" => "bar",
																	"a" => "b",
																	"b" => "c"));

			$c = $this->object->find(array('foo' => 'bar'), array('a'=>1, 'b'=>1));

			$this->assertTrue($c instanceof MongoCursor);
			$obj = $c->getNext();
			$this->assertEquals('b', $obj['a']);
			$this->assertEquals('c', $obj['b']);
			$this->assertEquals(false, array_key_exists('foo', $obj));
		}


		public function testFindWhere() {
				for($i=0;$i<50; $i++) {
						$this->object->insert(array( "foo$i" => pow(2, $i)));
				}

				$x = $this->object->findOne(array('$where' => new MongoCode('function() { return this.foo23 != null; }')));
				$this->assertArrayHasKey('foo23', $x, json_encode($x));
				$this->assertEquals(8388608, $x['foo23'], json_encode($x));
		}


		public function testFindOne() {
			$this->assertEquals(null, $this->object->findOne());
			$this->assertEquals(null, $this->object->findOne(array()));

			for ($i=0;$i<3;$i++) {
				$this->object->insert(array('x' => $i));
			}

			$obj = $this->object->findOne();
			$this->assertNotNull($obj);
			$this->assertEquals($obj['x'], 0);

			$obj = $this->object->findOne(array('x' => 1));
			$this->assertNotNull($obj);
			$this->assertEquals(1, $obj['x']);
		}

		public function testFindOneFields() {
			for ($i=0;$i<3;$i++) {
				$this->object->insert(array('x' => $i, 'y' => 4, 'z' => 6));
			}

			$obj = $this->object->findOne(array(), array('y'));
			$this->assertArrayHasKey('y', $obj, json_encode($obj));
			$this->assertArrayHasKey('_id', $obj, json_encode($obj));
			$this->assertArrayNotHasKey('x', $obj, json_encode($obj));
			$this->assertArrayNotHasKey('z', $obj, json_encode($obj));

			$obj = $this->object->findOne(array(), array('y'=>1, 'z'=>1));
			$this->assertArrayHasKey('y', $obj, json_encode($obj));
			$this->assertArrayHasKey('_id', $obj, json_encode($obj));
			$this->assertArrayNotHasKey('x', $obj, json_encode($obj));
			$this->assertArrayHasKey('z', $obj, json_encode($obj));
		}

		public function testUpdate() {
			$old = array("foo"=>"bar", "x"=>"y");
			$new = array("foo"=>"baz");

			$this->object->update(array("foo"=>"bar"), $old, array('upsert' => true));
			$obj = $this->object->findOne();
			$this->assertEquals($obj['foo'], 'bar');
			$this->assertEquals($obj['x'], 'y');

			$this->object->update($old, $new);
			$obj = $this->object->findOne();
			$this->assertEquals($obj['foo'], 'baz');
		}

		/**
		 * @expectedException MongoException
		 */
		public function testSafeUpdate1() {
			$this->object->update(array(), array('$inc' => array("foo" => "bar")), array("upsert" => true, "safe" => true));
		}

		public function testSafeUpdate2() {
			$result = $this->object->update(array(), array("foo" => "bar"), array("upsert" => true, "safe" => true));
			$this->assertEquals(true, (bool)$result['ok']);
			$this->assertNull($result['err']);
			$this->assertEquals(1, $result['n']);
			$this->assertFalse($result['updatedExisting']);

			$result = $this->object->update(array(), array('$set' => array("foo" => "baz")), array("upsert" => true, "safe" => true));
			$this->assertEquals(true, (bool)$result['ok']);
			$this->assertNull($result['err']);
			$this->assertEquals(1, $result['n']);
			$this->assertTrue($result['updatedExisting']);
		}


		public function testUpdateMultiple() {
			$this->object->insert(array("x" => 1));
			$this->object->insert(array("x" => 1));

			$this->object->insert(array("x" => 2, "y" => 3));
			$this->object->insert(array("x" => 2, "y" => 4));

			$this->object->update(array("x" => 1), array('$set' => array('x' => "hi")));
			// make sure one is set, one is not
			$this->assertNotNull($this->object->findOne(array("x" => "hi")));
			$this->assertNotNull($this->object->findOne(array("x" => 1)));

			// multiple update
			$this->object->update(array("x" => 2), array('$set' => array('x' => 4)), array("multiple" => true));
			$this->assertEquals(2, $this->object->count(array("x" => 4)));

			$cursor = $this->object->find(array("x" => 4))->sort(array("y" => 1));

			$obj = $cursor->getNext();
			$this->assertEquals(3, $obj['y']);
			$obj = $cursor->getNext();
			$this->assertEquals(4, $obj['y']);

			// check with upsert if there are matches
			$this->object->update(array("x" => 4), array('$set' => array("x" => 3)), array("upsert" => true, "multiple" => true));
			$this->assertEquals(2, $this->object->count(array("x" => 3)));

			$cursor = $this->object->find(array("x" => 3))->sort(array("y" => 1));

			$obj = $cursor->getNext();
			$this->assertEquals(3, $obj['y']);
			$obj = $cursor->getNext();
			$this->assertEquals(4, $obj['y']);

			// check with upsert if there are no matches
			$this->object->update(array("x" => 15), array('$set' => array("z" => 4)), array("upsert" => true, "multiple" => true));
			$this->assertNotNull($this->object->findOne(array("z" => 4)));

			$this->assertEquals(5, $this->object->count());
		}

		public function testRemove() {
			for($i=0;$i<15;$i++) {
				$this->object->insert(array("i"=>$i));
			}

			$this->assertEquals($this->object->count(), 15);
			$this->object->remove(array(), array('justOne' => true));
			$this->assertEquals($this->object->count(), 14);

			$this->object->remove(array());
			$this->assertEquals($this->object->count(), 0);

			for($i=0;$i<15;$i++) {
				$this->object->insert(array("i"=>$i));
			}

			$this->assertEquals($this->object->count(), 15);
			$this->object->remove();
			$this->assertEquals($this->object->count(), 0);
		}

		/**
		 * @expectedException MongoException
		 */
		public function testSafeRemove() {
			$indexes = $this->object->db->system->indexes;
			$indexes->remove(array(), array("safe" => true));
		}

		public function testSafeRemove2() {
			$result = $this->object->remove(array(), array("safe" => true));
			$this->assertEquals(true, (bool)$result['ok']);
			$this->assertEquals(0, $result['n']);
			$this->assertNull($result['err']);

			$this->object->batchInsert(array(array("x"=>1),array("x"=>1),array("x"=>1)));

			$result = $this->object->remove(array(), array("safe" => true));
			$this->assertEquals(true, (bool)$result['ok']);
			$this->assertEquals(3, $result['n']);
			$this->assertNull($result['err']);
		}

		public function testEnsureIndex() {
			$this->object->ensureIndex('foo');

			$idx = $this->object->db->selectCollection('system.indexes');
			$index = $idx->findOne(array('name' => 'foo_1'));

			$this->assertNotNull($index);
			$this->assertEquals($index['key']['foo'], 1);
			$this->assertEquals($index['name'], 'foo_1');

			$this->object->ensureIndex("");
			$index = $idx->findOne(array('name' => '_1'));
			$this->assertEquals(null, $index);

			// get rid of indexes
			$this->object->drop();

			$this->object->ensureIndex(null);
			$index = $idx->findOne(array('name' => '_1'));
			$this->assertEquals(null, $index);

			$this->object->ensureIndex(array('bar' => -1));
			$index = $idx->findOne(array('name' => 'bar_-1'));
			$this->assertNotNull($index);
			$this->assertEquals($index['key']['bar'], -1);
			$this->assertEquals($index['ns'], 'phpunit.c');
		}

		public function testEnsureUniqueIndex() {
			$unique = true;

			$this->object->ensureIndex(array('x'=>1), array('unique' => !$unique));
			$this->object->insert(array('x'=>0, 'z'=>1));
			$this->object->insert(array('x'=>0, 'z'=>2));
			$this->assertEquals($this->object->count(), 2);

			$this->object->ensureIndex(array('z'=>1), array('unique' => $unique));
			$this->object->insert(array('z'=>0));
			$this->object->insert(array('z'=>0));
			$err = $this->object->db->lastError();
			$this->assertEquals("E11000", substr($err['err'], 0, 6), json_encode($err));
		}

		public function testEnsureIndexOptions() {
			$this->object->insert(array('x' => 1));
			$this->object->insert(array('x' => 1));
			$this->object->insert(array('x' => 2));

			$this->object->ensureIndex(array('x' => 1), array('unique' => true, 'dropDups' => true));

			$this->assertEquals(2, $this->object->count());
			$this->object->insert(array('x' => 2));
			$this->assertEquals(2, $this->object->count());
		}

		public function testEnsureNamedIndex() {
			$db = $this->object->db;
			$this->object->ensureIndex(array("foo" => 1), array("name" => "bar"));
			$idx = $db->system->indexes->findOne(array("name" => "bar"));
			$this->assertNotNull($idx);
			$this->assertEquals("phpunit.c", $idx["ns"]);
			$this->assertEquals(1, $idx["key"]["foo"]);
		}

		public function testEnsureStringIndex() {
			$idx = array("x" => "2d");
			$this->object->ensureIndex($idx);
			$this->assertEquals("2d", $idx['x']);

			$idx = $this->object->db->system->indexes->findOne(array("name" => "x_2d"));
			$this->assertNotNull($idx);
			$this->assertEquals("phpunit.c", $idx["ns"]);
			$this->assertEquals("2d", $idx["key"]["x"]);

			$cursor = $this->object->find(array("x" => array( "\$near" => array('x' => 44, 'y' => 93))));
			$cursor->hasNext();
		}

		public function testDeleteIndex() {
			$idx = $this->object->db->selectCollection('system.indexes');

			$this->object->ensureIndex('foo');
			$this->object->ensureIndex(array('foo' => -1));

			$cursor = $idx->find(array('ns' => 'phpunit.c'));
			$num = iterator_count($cursor);
			$this->assertEquals(3, $num);

			$this->object->deleteIndex(array('foo' => 1));
			$num = iterator_count($idx->find(array('ns' => 'phpunit.c')));
			$this->assertEquals(2, $num);

			$this->object->deleteIndex('foo');
			$num = iterator_count($idx->find(array('ns' => 'phpunit.c')));
			$this->assertEquals(2, $num);

			$this->object->deleteIndex(array('foo' => -1));
			$num = iterator_count($idx->find(array('ns' => 'phpunit.c')));
			$this->assertEquals(1, $num);
		}

		public function testDeleteIndexBroken() {
			$idx = $this->object->db->selectCollection('system.indexes');

			$this->object->ensureIndex('foo');
			$this->object->ensureIndex(array('foo' => -1));

			$cursor = $idx->find(array('ns' => 'phpunit.c'));
			$num = iterator_count($cursor);
			$this->assertEquals(3, $num);

		set_error_handler(array('MongoCollectionTest', 'errorHandler'));
		try {
				$this->object->deleteIndex(null);
		} catch (Exception $e) {
			$this->assertEquals("HANDLED: MongoCollection::deleteIndex(): The key needs to be either a string or an array", $e->getMessage());
		}
		restore_error_handler();
			$num = iterator_count($idx->find(array('ns' => 'phpunit.c')));
			$this->assertEquals(3, $num);
		}

		public function testDeleteIndexes() {
			$idx = $this->object->db->selectCollection('system.indexes');

			$this->object->ensureIndex(array('foo' => 1));
			$this->object->ensureIndex(array('foo' => -1));
			$this->object->ensureIndex(array('bar' => 1, 'baz' => -1));

			$num = iterator_count($idx->find(array('ns' => 'phpunit.c')));
			$this->assertEquals($num, 4);

			$this->object->deleteIndexes();
			$num = iterator_count($idx->find(array('ns' => 'phpunit.c')));
			$this->assertEquals($num, 1);
		}

		public function testGetIndexInfo() {
			$info = $this->object->getIndexInfo();
			$this->assertEquals(count($info), 0);

			$this->object->ensureIndex(array('foo' => 1));
			$this->object->ensureIndex(array('foo' => -1));
			$this->object->ensureIndex(array('bar' => 1, 'baz' => -1));

			$info = $this->object->getIndexInfo();
			$this->assertEquals(4, count($info), json_encode($info));
			$this->assertEquals($info[1]['key']['foo'], 1);
			$this->assertEquals($info[1]['name'], 'foo_1');
			$this->assertEquals($info[2]['key']['foo'], -1);
			$this->assertEquals($info[2]['name'], 'foo_-1');
			$this->assertEquals($info[3]['key']['bar'], 1);
			$this->assertEquals($info[3]['key']['baz'], -1);
			$this->assertEquals($info[3]['name'], 'bar_1_baz_-1');
		}

		public function testCount() {
			$this->assertEquals($this->object->count(), 0);

			$this->object->insert(array(6));

			$this->assertEquals($this->object->count(), 1);

			$this->assertEquals(0, $this->object->count(array('z'=>1)));
			$this->assertEquals(1, $this->object->count(array('0'=>6)));
		}


		public function testSave() {
			$this->object->save(array('x' => 1));

			$a = $this->object->findOne();
			$id1 = $a['_id'];

			$a['x'] = 2;
			$this->object->save($a);
			$id2 = $a['_id'];

			$this->assertEquals($id1, $id2);
			$a['y'] = 3;
			$this->object->save($a);

			$this->assertEquals($this->object->count(), 1);

			$a = $this->object->findOne();
			$this->assertEquals($a['x'], 2);
		}

		public function testSafeSave() {
			$result = $this->object->save(array("x"=>1), array("safe" => true));
			$this->assertEquals(true, (bool)$result['ok']);
			$this->assertEquals(0, $result['n']);
			$this->assertNull($result['err']);

			$x = $this->object->findOne();

			$result = $this->object->save($x, array("safe" => true));
			$this->assertEquals(true, (bool)$result['ok']);
			$this->assertEquals(1, $result['n']);
			$this->assertTrue($result['updatedExisting']);
			$this->assertNull($result['err']);
		}

		public function testGetDBRef() {
				for($i=0;$i<50;$i++) {
						$this->object->insert(array('x' => rand()));
				}
				$obj = $this->object->findOne();

				$ref = $this->object->createDBRef($obj);
				$obj2 = $this->object->getDBRef($ref);

				$this->assertNotNull($obj2);
				$this->assertEquals($obj['x'], $obj2['x']);
		}

		public function testCreateDBRef() {
				$ref = $this->object->createDBRef(array('foo' => 'bar'));
				$this->assertEquals($ref, null);

				$arr = array('_id' => new MongoId());
				$ref = $this->object->createDBRef($arr);
				$this->assertNotNull($ref);
				$this->assertTrue(is_array($ref));

				$arr = array('_id' => 1);
				$ref = $this->object->createDBRef($arr);
				$this->assertNotNull($ref);
				$this->assertTrue(is_array($ref));

				$ref = $this->object->createDBRef(new MongoId());
				$this->assertNotNull($ref);
				$this->assertTrue(is_array($ref));
		}


		public function testGroup() {
				$g = $this->object->group(array(), array("count"=> 0), "function (obj, prev) { prev.count++; }", array());
				$this->assertEquals(0, count($g['retval']));

				$this->object->save(array("a" => 2));
				$this->object->save(array("b" => 5));
				$this->object->save(array("a" => 1));

				$g = $this->object->group(array(), array("count" => 0), "function (obj, prev) { prev.count++; }", array());
				$this->assertEquals(1, count($g['retval']));
				$this->assertEquals(3, $g['retval'][0]['count']);

				$g = $this->object->group(array(), array("count" => 0), "function (obj, prev) { prev.count++; }", array("a" => array( '$gt' => 1)));
				$this->assertEquals(1, count($g['retval']));
				$this->assertEquals(1, $g['retval'][0]['count']);
	 }

		public function testGroup2() {
				$this->object->save(array("a" => 2));
				$this->object->save(array("b" => 5));
				$this->object->save(array("a" => 1));
				$keys = array();
				$initial = array("count" => 0);
				$reduce = "function (obj, prev) { prev.count++; }";

				$g = $this->object->group($keys, $initial, $reduce);
				$this->assertEquals(3, $g['count']);
		}

		public function testSafeInsert() {
			$c = $this->object;
			$c->drop();

			$success = $c->insert(array("_id" => "foo"));
			$this->assertTrue($success);
			$success = $c->insert(array("_id" => "foo"));
			$this->assertTrue($success);
		}

		/**
		 * @expectedException MongoCursorException
		 */
		public function testSafeInsert2() {
			$c = $this->object;
			$c->drop();

			$success = $c->insert(array("_id" => "bar"), array('safe' => true));
			$this->assertEquals($success['err'], null);
			$c->insert(array("_id" => "bar"), array('safe' => true));
		}

		public function testGroupKeyf() {
			// group by divisors
			for ($i=0; $i<100; $i++) {
				$this->object->insert(array("x" => $i));
			}

			$result = $this->object->group(new MongoCode("function(doc) { return {mod : doc.x % 7}; }"),
																		 array("count" => 0),
																		 new MongoCode("function(doc, total) { total.count++; }"));

			$this->assertEquals(100, $result['count']);
			$this->assertEquals(14, $result['retval'][6]['count']);
			$this->assertEquals(6, $result['retval'][6]['mod']);
			$this->assertEquals(15, $result['retval'][1]['count']);
			$this->assertEquals(1, $result['retval'][1]['mod']);
		}

		/**
		 * @expectedException MongoException
		 */
		public function testInvalidKey() {
			$this->object->group("key", array("count" => 0), "reduce");
		}

		public function testFields() {
			$this->object->insert(array("x" => 1, "y" => 1));

			$cursor = $this->object->find(array(), array("x" => false));
			$r = $cursor->getNext();
			$this->assertTrue(array_key_exists("_id", $r));
			$this->assertTrue(array_key_exists("y", $r));
			$this->assertFalse(array_key_exists("x", $r));

			// make sure this is ok
			$cursor = $this->object->find(array(), array("x" => array()));
			$r = $cursor->getNext();
			$this->assertTrue(array_key_exists("_id", $r));
			$this->assertTrue(array_key_exists("x", $r));
			$this->assertFalse(array_key_exists("y", $r));
		}

		public function testGroupFinalize() {
			$this->object->insert(array("i" => 1, "j" => 3));
			$this->object->insert(array("i" => 1, "j" => 3));
			$this->object->insert(array("i" => 2, "j" => 4));

			$group = $this->object->group(array("i" => 1),
													 array("count" => 0),
													 new MongoCode("function(obj, prev) { prev.count += obj.j; }"),
													 array("finalize" => new MongoCode("function(obj) { return 'total: '+obj.count; }")));

			$this->assertEquals(true, (bool)$group['ok'], json_encode($group));

			$this->assertEquals("total: 6", $group['retval'][0], json_encode($group));
			$this->assertEquals("total: 4", $group['retval'][1], json_encode($group));
			$this->assertEquals(3, $group['count'], json_encode($group));
			$this->assertEquals(2, $group['keys'], json_encode($group));

		}
		public function testGroupFandC() {
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 3));
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 3));
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 4));
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 4));
			$this->object->insert(array("i" => 3, "j" => 1, "k" => 1));

			$group = $this->object->group(array("k" => 1),
																		array("count" => 0),
																		new MongoCode("function(obj, prev) { prev.count++; }"),
																		array("finalize" => new MongoCode("function(obj) { return obj.count; }"),
																					"condition" => array("i" => 1)));

			$this->assertEquals(true, (bool)$group['ok'], json_encode($group));

			$this->assertEquals(2, $group['retval'][0], json_encode($group));
			$this->assertEquals(2, $group['retval'][1], json_encode($group));
			$this->assertEquals(4, $group['count'], json_encode($group));
			$this->assertEquals(2, $group['keys'], json_encode($group));
		}

		public function testGroupNeitherOpt() {
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 3));
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 3));
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 4));
			$this->object->insert(array("i" => 1, "j" => 2, "k" => 4));
			$this->object->insert(array("i" => 3, "j" => 1, "k" => 1));

			$group = $this->object->group(array("k" => 1),
																		array("count" => 0),
																		new MongoCode("function(obj, prev) { prev.count++; }"),
																		array());

			$this->assertEquals(true, (bool)$group['ok'], json_encode($group));

			$this->assertEquals(2, $group['retval'][0]['count'], json_encode($group));
			$this->assertEquals(2, $group['retval'][1]['count'], json_encode($group));
			$this->assertEquals(1, $group['retval'][2]['count'], json_encode($group));
			$this->assertEquals(5, $group['count'], json_encode($group));
			$this->assertEquals(3, $group['keys'], json_encode($group));
		}

		public function testW() {
			$this->assertEquals(1, $this->object->w);
			$this->assertEquals(10000, $this->object->wtimeout);

			$this->object->w = 4;
			$this->object->wtimeout = 60;

			$this->assertEquals(4, $this->object->w);
			$this->assertEquals(60, $this->object->wtimeout);
		}

		public function testWInherit() {
			$db = $this->object->db;

			$db->w = 4;
			$db->wtimeout = 60;

			$c = $db->foo;

			$this->assertEquals(4, $c->w);
			$this->assertEquals(60, $c->wtimeout);
		}

		public function testGroupFinalize2() {
			for ($i=0; $i<100; $i++) {
				$this->object->insert(array("x" => $i, "y" => $i%7, "z" => "foo$i"));
			}

			$result = $this->object->group(array("y" => 1), array("count" => 0),
																		 "function(cur, prev) { prev.count++; }",
																		 array("finalize" => "function(out) { return 1; }"));

			$this->assertEquals(true, (bool)$result['ok'], json_encode($result));
			foreach ($result['retval'] as $val) {
				$this->assertEquals(1, $val);
			}

			$this->assertEquals(100, $result['count']);
			$this->assertEquals(7, $result['keys']);
		}

		public function testConsts() {
			$this->assertEquals(1, MongoCollection::ASCENDING);
			$this->assertEquals(-1, MongoCollection::DESCENDING);
		}

		public function testTags() {
				// does not throw in 1.8
				try {
						$this->object->insert(array("x"=>1), array("safe" => "foo", "wtimeout" => 1000));
				}
				catch (MongoCursorException $e) {}
		}
}

class TestToIndexString extends MongoCollection {
		public static function test($obj) {
				return parent::toIndexString($obj);
		}
}

?>
