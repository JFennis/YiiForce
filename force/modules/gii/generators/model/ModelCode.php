<?php

class ModelCode extends CCodeModel
{
	public $connectionId='forceConnection';
	public $tablePrefix;
	public $tableName;
	public $modelClass;
	public $modelPath='force.models';
	public $baseClass='ForceObject';
	public $buildRelations=true;
	public $commentsAsLabels=false;

	/**
	 * @var array list of candidate relation code. The array are indexed by AR class names and relation names.
	 * Each element represents the code of the one relation in one AR class.
	 */
	protected $relations;

	public function rules()
	{
		return array_merge(parent::rules(), array(
			array('tablePrefix, baseClass, tableName, modelClass, modelPath, connectionId', 'filter', 'filter'=>'trim'),
			array('connectionId, tableName, modelPath, baseClass', 'required'),
			array('tablePrefix, tableName, modelPath', 'match', 'pattern'=>'/^(\w+[\w\.]*|\*?|\w+\.\*)$/', 'message'=>'{attribute} should only contain word characters, dots, and an optional ending asterisk.'),
			array('connectionId', 'validateConnectionId', 'skipOnError'=>true),
			array('tableName', 'validateTableName', 'skipOnError'=>true),
			array('tablePrefix, modelClass', 'match', 'pattern'=>'/^[a-zA-Z_]\w*$/', 'message'=>'{attribute} should only contain word characters.'),
		    array('baseClass', 'match', 'pattern'=>'/^[a-zA-Z_][\w\\\\]*$/', 'message'=>'{attribute} should only contain word characters and backslashes.'),
			array('modelPath', 'validateModelPath', 'skipOnError'=>true),
			array('baseClass, modelClass', 'validateReservedWord', 'skipOnError'=>true),
			array('baseClass', 'validateBaseClass', 'skipOnError'=>true),
			array('connectionId, tablePrefix, modelPath, baseClass, buildRelations, commentsAsLabels', 'sticky'),
		));
	}

	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), array(
			'tablePrefix'=>'Prefix',
			'tableName'=>'sObject Name',
			'modelPath'=>'Model Path',
			'modelClass'=>'Model Class',
			'baseClass'=>'Base Class',
			'buildRelations'=>'Build Relations',
			'commentsAsLabels'=>'Use Column Comments as Attribute Labels',
			'connectionId'=>'Database Connection',
		));
	}

	public function requiredTemplates()
	{
		return array(
			'model.php',
		);
	}

	public function init()
	{
		parent::init();
	}

	public function prepare()
	{
		$tableName = $this->tableName;
        $fields = Yii::app()->getModule('force')->forceConnection->getObjectMetaData($tableName)->fields;
		$this->files = array();
		$templatePath = $this->templatePath;
    	$className = $this->modelClass;

		$params=array(
			'tableName'=>$tableName,
			'modelClass'=>$className,
			'columns'=>$fields,
			'labels'=>$this->generateLabels($fields),
			'rules'=>$this->generateRules($fields),
			'connectionId'=>$this->connectionId,
		);

		$this->files[] = new CCodeFile(
			Yii::getPathOfAlias($this->modelPath).'/'.$className.'.php',
			$this->render($templatePath.'/model.php', $params)
		);
	}


	public function validateTableName($attribute,$params)
	{
		if($this->hasErrors())
			return;

		if(Yii::app()->getModule('force')->forceConnection->getSchema()->getObjects($this->tableName) === null)
			$this->addError('tableName',"sObject '{$this->tableName}' does not exist.");

		if($this->modelClass==='')
			$this->addError('modelClass','Model Class cannot be blank.');
	}

	public function validateModelPath($attribute,$params)
	{
		if(Yii::getPathOfAlias($this->modelPath) ===false )
			$this->addError('modelPath','Model Path must be a valid path alias.');
	}

	public function validateConnectionId($attribute, $params)
	{
		if(Yii::app()->getModule('force')->forceConnection===false || !(Yii::app()->getModule('force')->forceConnection) instanceof ForceConnection)
			$this->addError('connectionId','forceConnection component not found in module force');
	}

	public function validateBaseClass($attribute,$params)
	{
		$class=@Yii::import($this->baseClass,true);

		if(!is_string($class) || !$this->classExists($class))
			$this->addError('baseClass', "Class '{$this->baseClass}' does not exist or has syntax error.");

		elseif($class!=='ForceObject' && !is_subclass_of($class,'ForceObject'))
			$this->addError('baseClass', "'{$this->model}' must extend from ForceObject.");
	}


	public function generateLabels($fields)
	{
		$labels=array();
		foreach($fields as $name=>$column)
		{
    		$labels[$name] = preg_replace('@[\'!:;_\?=\\\+\*/%&#]+@', '-', $column['label']);
		}
		return $labels;
	}

	public function generateRules($fields)
	{
		$rules=array();

		$required=array();
		$integers=array();
		$numerical=array();
		$length=array();
        $date=array();
        $email=array();
        $url=array();
		$safe=array();

		foreach($fields as $name=>$column)
		{
             $safe[] = $column['name'];

            //Fields not requiring validation
			if($column['deprecatedAndHidden'] || !$column['updateable'] || $column['calculated'] || $column['autoNumber'] || $column['type'] === 'reference' || $column['type'] === 'id')
				continue;

            //Required fields
			if(!$column['nillable'] && $column['defaultValue']===null && $column['type']!='boolean')
				$required[]=$column['name'];

            //type checking
            switch($column['type']){
                case 'int':
                    $integers[] = $column['name'];
                    break;
                case 'double':
                    $numerical[] = $column['name'];
                    break;
                case 'currency':
                    $numerical[] = $column['name'];
                    break;
                case 'percent':
                    $numerical[] = $column['name'];
                    break;
                case 'string':
                    if($column['length']>0)
                        $length[$column['length']][] = $column['name'];
                    break;
                case 'phone':
                    if($column['length']>0)
                        $length[$column['length']][] = $column['name'];
                    break;
                case 'datetime':
                    $date[] = $column['name'];
                    break;
                case 'date':
                    $date[] = $column['name'];
                    break;
                case 'time':
                    $date[] = $column['name'];
                    break;
                case 'email':
                    $email[] = $column['name'];
                    break;
                case 'url':
                    $url[] = $column['name'];
                    break;
            }
				
		}

		if($required!==array())
			$rules[]="array('".implode(', ',$required)."', 'required')";
		if($integers!==array())
			$rules[]="array('".implode(', ',$integers)."', 'numerical', 'integerOnly'=>true)";
		if($numerical!==array())
			$rules[]="array('".implode(', ',$numerical)."', 'numerical')";
		if($length!==array())
		{
			foreach($length as $len=>$cols)
				$rules[]="array('".implode(', ',$cols)."', 'length', 'max'=>$len)";
		}
		if($date!==array())
			$rules[]="array('".implode(', ',$date)."', 'date')";
		if($email!==array())
			$rules[]="array('".implode(', ',$email)."', 'email')";
		if($url!==array())
			$rules[]="array('".implode(', ',$url)."', 'url')";
		if($safe!==array())
			$rules[]="array('".implode(', ',$safe)."', 'safe')";

		return $rules;
	}
}