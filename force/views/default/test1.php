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



$conn = Yii::app()->getModule('force')->forceConnection;
$schema = $conn->getSchema();


$account = $schema->getInstance('Account');
$account->unsetAttributes();
$dataProvider = $account->search();
$data = $dataProvider->calculateTotalItemCount();


//CVarDumper::dump($data, 3, true);

$this->widget('zii.widgets.grid.CGridView', array( 
    'id'=>'account-grid', 
    'dataProvider'=>$account->search(), 
    'columns'=>array( 
        'Id',
        'Name',
    ), 
));

?>