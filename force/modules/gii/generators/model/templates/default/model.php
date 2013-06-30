<?php
/**
 * This is the template for generating the model class of a specified table.
 * - $this: the ModelCode object
 * - $tableName: the table name for this class (prefix is already removed if necessary)
 * - $modelClass: the model class name
 * - $columns: list of table columns (name=>CDbColumnSchema)
 * - $labels: list of attribute labels (name=>label)
 * - $rules: list of validation rules
 * - $relations: list of relations (name=>relation declaration)
 */
?>
<?php echo "<?php\n"; ?>


class <?php echo $modelClass; ?> extends <?php echo $this->baseClass."\n"; ?>
{

<?php foreach($columns as $column): ?>
    <?php echo "protected $" . $column['name'] . ";\t// type: " . $column['type'] . "\n"; ?>
<?php endforeach; ?>

    public $sObject='<?php echo $tableName; ?>';


	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
<?php foreach($rules as $rule): ?>
			<?php echo $rule.",\n"; ?>
<?php endforeach; ?>
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('<?php echo implode(', ', array_keys($columns)); ?>', 'safe', 'on'=>'search'),
		);
	}


	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
<?php foreach($labels as $name=>$label): ?>
			<?php echo "'$name' => '$label',\n"; ?>
<?php endforeach; ?>
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function searchCriteria()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria = new ForceCriteria;

<?php
foreach($columns as $name=>$column)
{
	if($column['type']==='string')
	{
		echo "\t\t\$criteria->compare('$name',\$this->$name,true);\n";
	}
	else
	{
		echo "\t\t\$criteria->compare('$name',\$this->$name);\n";
	}
}
?>

        return $criteria;
	}


    public function search()
    {

		return new ForceDataProvider($this, array(
			'criteria'=>$this->searchCriteria(),
		));
    }


	/**
	 * Property getter and setters for magic methods
	 */

<?php foreach($columns as $column): ?>
	public function get<?php echo $column['name'] ?>()
	{
		return $this-><?php echo $column['name'] . ";\n"; ?>
	}

	public function set<?php echo $column['name'] ?>($value)
	{
		$this-><?php echo $column['name'] . " = \$value;\n"; ?>
	}

<?php endforeach; ?>
}
