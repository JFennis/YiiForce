<?php
/* @var $this DefaultController */

$this->breadcrumbs=array(
	'Force',
);

$this->menu=array(
	array('label'=>'Force.com Gii', 'url'=>array('/force/gii')),
    array('label'=>'Test Page 1', 'url'=>array('test1')),
    array('label'=>'Test Page 2', 'url'=>array('test2')),
);

?>

<h1>Force.com sObject Manager</h1>

<h2>Available Objects</h2>

<?php 
$this->widget('CTreeView',array(
    'data'=>$this->getTreeView(),
    'animated'=>'fast', 
    'collapsed'=>'true',
    'htmlOptions'=>array(
         'class'=>'treeview-blue',
         ),
     ));
?> 