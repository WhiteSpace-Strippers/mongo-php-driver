--TEST--
Test for PHP-672: MongoGridFSFile::write() leaks memory
--SKIPIF--
<?php require_once dirname(__FILE__) . "/skipif.inc";?>
--FILE--
<?php
require_once dirname(__FILE__) . "/../utils.inc";
$mongo = new_mongo();
$gridfs = $mongo->files->getGridFS();
$i = 0;
foreach(glob(dirname(__FILE__) . "/*") as $file) {
		$gridfs->put($file);
		if ($i++ > 10) {
				break;
		}
}

$file = $mongo->files->getGridFS()->find()->sort(array('length' => -1))->limit(1)->getNext();

$attempts = 10;
while ($attempts--) {
	$mongo->files->getGridFS()->find()->sort(array('length' => -1))->limit(1)->getNext()->write('./test.bin');
}
@unlink("./test.bin");

echo "No memory leaks should be reported\n";
?>
--EXPECTF--
No memory leaks should be reported

