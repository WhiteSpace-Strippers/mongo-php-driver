--TEST--
MongoCursor::setReadPreference errors [1]
--SKIPIF--
<?php require_once dirname(__FILE__) ."/skipif.inc"; ?>
--FILE--
<?php require_once dirname(__FILE__) ."/skipif.inc"; ?>
<?php
$host = hostname();
$port = port();
$db	 = dbname();

$baseString = sprintf("mongodb://%s:%d/%s?readPreference=primaryPreferred", $host, $port, $db);

$b = array("blaat", 42, true, 3.14);

foreach ($b as $newRP) {
	$m = new mongo($baseString);
	$c = $m->$db->readpref->find();
	$c->setReadPreference($newRP);
	$rp = $c->getReadPreference();
	echo $rp["type"], "\n";
}
?>
--EXPECTF--
Warning: MongoCursor::setReadPreference(): The value 'blaat' is not valid as read preference type in %smongocursor-setreadpreference_error-001.php on line %d
primaryPreferred

Warning: MongoCursor::setReadPreference(): The value '42' is not valid as read preference type in %smongocursor-setreadpreference_error-001.php on line %d
primaryPreferred

Warning: MongoCursor::setReadPreference(): The value '1' is not valid as read preference type in %smongocursor-setreadpreference_error-001.php on line %d
primaryPreferred

Warning: MongoCursor::setReadPreference(): The value '3.14' is not valid as read preference type in %smongocursor-setreadpreference_error-001.php on line %d
primaryPreferred
