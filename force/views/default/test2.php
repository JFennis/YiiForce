<?php
/* @var $this EmployeeController */
/* @var $model Employee */

$this->breadcrumbs=array(
	'Force'=>array('index'),
	'Test',
);

$this->menu=array(
	array('label'=>'Force.com', 'url'=>array('index')),
);
?>

<h1>Test Page</h1>

<?php 

$object = new Contact;
$conn = $object->getForceConnection();

$query = "SELECT Id, Name FROM Account";
$records = $conn->sqlQuery($query);

$refList=array();
foreach($records as $record){
    $id = $record['Id'];
    $refList[$id] = $record[$name];
}

CVarDumper::dump($records);

/*
$value = DateTime::createFromFormat('Y-m-d H:i:s', '2013-06-26 10:12:00', new DateTimeZone('UTC'));
$value1= date_format($value, 'c');
CVarDumper::dump($value);
CVarDumper::dump('   VALUE 1 '. $value1);
*/


?>