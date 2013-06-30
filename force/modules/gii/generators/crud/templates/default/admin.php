<?php
/**
 * The following variables are available in this template:
 * - $this: the CrudCode object
 */
?>
<?php echo "<?php\n"; ?>
/* @var $this <?php echo $this->getControllerClass(); ?> */
/* @var $model <?php echo $this->getModelClass(); ?> */

<?php
$label=$this->pluralize($this->class2name($this->modelClass));
echo "\$this->breadcrumbs=array(
    'Force'=>array('/force'),
	'$label'=>array('/force/$this->modelClass'),
	'Manage',
);\n";
?>

$this->menu=array(
	array('label'=>'List <?php echo $this->modelClass; ?>', 'url'=>array('index')),
	array('label'=>'Create <?php echo $this->modelClass; ?>', 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#<?php echo $this->class2id($this->modelClass); ?>-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1>Manage <?php echo $this->pluralize($this->class2name($this->modelClass)); ?></h1>


<p>
You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b>
or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.
</p>

<?php echo "<?php echo CHtml::link('Advanced Search','#',array('class'=>'search-button')); ?>"; ?>

<div class="search-form" style="display:none">
<?php echo "<?php \$this->renderPartial('_search',array(
	'model'=>\$model,
)); ?>\n"; ?>
</div><!-- search-form -->

<div id="statusMsg">
<?php echo "<?php if(Yii::app()->user->hasFlash('success')):?>\n"; ?>
    <div class="flash-success">
        <?php echo "<?php echo Yii::app()->user->getFlash('success'); ?>\n"; ?>
    </div>
<?php echo "<?php endif; ?>"; ?>
 
<?php echo "<?php if(Yii::app()->user->hasFlash('error')):?>\n"; ?>
    <div class="flash-error">
        <?php echo "<?php echo Yii::app()->user->getFlash('error'); ?>\n"; ?>
        <div class="errorMsg">Error Message</div>
    </div>
<?php echo "<?php endif; ?>\n"; ?>
</div>

<?php echo "<?php"; ?> $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'<?php echo $this->class2id($this->modelClass); ?>-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
    'beforeAjaxUpdate'=>'function(id, data){ $("#statusMsg").html(""); $("#searchCriteria").html(""); }',
	'columns'=>array(
<?php
$count=0;
foreach($this->tableFields as $name=>$column)
{
    if(substr_count("&id& &reference& &boolean& &encryptedstring& &textarea&", "&".$column['type']."&") == 0 &&
       substr_count("&CreatedDate& &LastModifiedDate& &SystemModstamp&", "&".$column['name']."&") == 0){
	    if(++$count >= 7)
		    echo "\t\t//".$column['name']."',\n";
        else
            echo "\t\t'".$column['name']."',\n";   
    } else {
        echo "\t\t//".$column['name']."',\n";
    }
}
?>
		array(
			'class'=>'CButtonColumn', 
            'afterDelete'=>'function(link,success,data){ if(success) $("#statusMsg").html(data);  }',
		),
	),
)); ?>
