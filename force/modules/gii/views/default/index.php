<h1>Welcome to Yii Force.com sObject Generator!</h1>

<p>
	Start by using the Model Generator to create an sObject model representation within your Yii application. Subsequently use the Crud Generator to create web pages to manage your sObject.
</p>
<ul>
	<?php foreach($this->module->controllerMap as $name=>$config): ?>
	<li><?php echo CHtml::link(ucwords(CHtml::encode($name).' generator'),array($name.'/index'));?></li>
	<?php endforeach; ?>
</ul>

<?php


?>

