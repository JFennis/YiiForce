<?php
/**
 * The following variables are available in this template:
 * - $this: the CrudCode object
 */
?>
<?php echo "<?php\n"; ?>
/* @var $this <?php echo $this->getControllerClass(); ?> */
/* @var $data <?php echo $this->getModelClass(); ?> */
?>

<div class="view">

<?php
echo "\t<b><?php echo CHtml::encode(\$data->getAttributeLabel('Id')); ?>:</b>\n";
echo "\t\t<?php echo CHtml::link(CHtml::encode(\$data->Id), array('view', 'id'=>\$data->Id)); ?>\n\t<br />\n\n";
$count=0;
foreach($this->tableFields as $name=>$column)
{
    if($column['name']==='Name')
        continue;

    if(substr_count("id Name reference boolean encrypted textarea", $column['type']) == 0){
	    if(++$count >= 7){
	        echo "\t\t<?php //echo CHtml::encode(\$data->getAttributeLabel('{$column['name']}')); ?> \n";
            echo "\t\t<?php //echo CHtml::encode(\$data->{$column['name']}); ?> \n\n";
	    } else {
	        echo "\t\t<b><?php echo CHtml::encode(\$data->getAttributeLabel('{$column['name']}')); ?>:</b>\n";
            echo "\t\t<?php echo CHtml::encode(\$data->{$column['name']}); ?>\n\t<br />\n\n";	        
	    }
    } else {
	        echo "\t\t<?php //echo CHtml::encode(\$data->getAttributeLabel('{$column['name']}')); ?> \n";
            echo "\t\t<?php //echo CHtml::encode(\$data->{$column['name']}); ?> \n\n";
    }
}
?>
</div>