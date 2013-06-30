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
$nameColumn = $this->guessNameColumn($this->tableFields);
$label=$this->pluralize($this->class2name($this->modelClass));
echo "\$this->breadcrumbs=array(
    'Force'=>array('/force'),
	'$label'=>array('/force/$this->modelClass'),
	\$model->{$nameColumn},
);\n";

?>

$this->menu=array(
	array('label'=>'List <?php echo $this->modelClass; ?>', 'url'=>array('index')),
	array('label'=>'Create <?php echo $this->modelClass; ?>', 'url'=>array('create')),
	array('label'=>'Update <?php echo $this->modelClass; ?>', 'url'=>array('update', 'id'=>$model->Id)),
	array('label'=>'Delete <?php echo $this->modelClass; ?>', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->Id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage <?php echo $this->modelClass; ?>', 'url'=>array('admin')),
);
?>

<h1>View <?php echo $this->modelClass." #<?php echo \$model->$nameColumn; ?>"; ?></h1>

<div id="statusMsg">
<?php echo "<?php if(Yii::app()->user->hasFlash('success')):?>\n"; ?>
    <div class="flash-success">
        <?php echo "<?php echo Yii::app()->user->getFlash('success'); ?>\n"; ?>
    </div>
<?php echo "<?php endif; ?>"; ?>
 
<?php echo "<?php if(Yii::app()->user->hasFlash('error')):?>\n"; ?>
    <div class="flash-error">
        <?php echo "<?php echo Yii::app()->user->getFlash('error'); ?>\n"; ?>
    </div>
<?php echo "<?php endif; ?>\n"; ?>
</div>

<?php echo "<?php"; ?> $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
        <?php
        foreach($this->tableFields as $column){
            if($column['type'] === 'reference'){
                echo "\t\tarray(\n";
                echo "\t\t\t'label'=>'".$column['name']."',\n";
                echo "\t\t\t'type'=>'raw',\n";
                echo "\t\t\t'value'=>\$this->getParentValue(\$model,'" . $column['name'] . "'),\n";
                echo "\t\t),\n";
            } elseif($column['type'] === 'url') {
                echo "\t\tarray(\n";
                echo "\t\t\t'label'=>'".$column['name']."',\n";
                echo "\t\t\t'type'=>'raw',\n";
                echo "\t\t\t'value'=>CHtml::link(\$model->{$column['name']}, \$model->{$column['name']}),\n";
                echo "\t\t),\n";   
            }
            else
    	        echo "\t\t'".$column['name']."',\n";
        }
        echo "\t\tarray(\n";
        echo "\t\t\t'label'=>'Child Relationships',\n";
        echo "\t\t\t'type'=>'html',\n";
        echo "\t\t\t'value'=>\$this->getChildRelationships(\$model),\n";
        echo "\t\t),\n";
        ?>
	),
));
