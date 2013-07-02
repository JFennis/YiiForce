<?php
/**
 * This is the template for generating a controller class file for CRUD feature.
 * The following variables are available in this template:
 * - $this: the CrudCode object
 */
?>
<?php echo "<?php\n"; ?>

class <?php echo $this->controllerClass; ?> extends <?php echo $this->baseControllerClass."\n"; ?>
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('index','view'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{

		$model = new <?php echo $this->modelClass; ?>;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['<?php echo $this->modelClass; ?>']))
		{
			$model->attributes=$_POST['<?php echo $this->modelClass; ?>'];
			if(($result = $model->save()) === true)
				$this->redirect(array('view','id'=>$model->Id));
            else
                $model->addError('Id', $result);
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model = $this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['<?php echo $this->modelClass; ?>']))
		{
			$model->attributes=$_POST['<?php echo $this->modelClass; ?>'];
			if(($result = $model->save()) === true)
				$this->redirect(array('view','id'=>$model->Id));
            else
                $model->addError('Id', $result);
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
        
        $result = $this->loadModel($id)->delete();
        if($result === true){
            if(!isset($_GET['ajax']))
                Yii::app()->user->setFlash('success','Deleted Successfully');
            else
                echo "<div class='flash-success'>Deleted Successfully</div>";
        } else {
            if(!isset($_GET['ajax'])){
                Yii::app()->user->setFlash('error',$result);
                $this->redirect(array('view', 'id'=>$id));
            }
            else
                echo "<div class='flash-error'>".$result."</div>"; 
        }

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider = new ForceDataProvider('<?php echo $this->modelClass; ?>', array(
            'pagination'=>array(
                'pageSize'=>5,
            ),
        ));

        $model = new <?php echo $this->modelClass; ?>;
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
            'model'=>$model,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin($field=null,$value=null)
	{
		$model = new <?php echo $this->modelClass; ?>;
		$model->unsetAttributes();  

		if(isset($_GET['<?php echo $this->modelClass; ?>']))
			$model->attributes=$_GET['<?php echo $this->modelClass; ?>'];
        elseif($field != null)
            $model->$field = $value;

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return <?php echo $this->modelClass; ?> the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
        $conn = Yii::app()->getModule('force')->forceConnection;
        $schema = $conn->getSchema();

		$model = $schema->findByPk('<?php echo $this->modelClass; ?>',$id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

    public function getPickList($model, $name)
    {
        $pickList=array();
        $items = $model->metaData->fields[$name]['picklistValues'];
        foreach($items as $item){
            $pickList[$item['value']] = $item['label'];
        }
        return array_merge(array(null=>' '), $pickList);
    }

    public function getReference($model, $column)
    {
        return $references = $model->getReferenceData($column);
    }

    public function getParentValue($model, $column)
    {
        $result = $model->getParentValue($column);
        if($result != null) {
            $modelName = $model->schema->getObjectMapping($result['object']);
            if($modelName != null)
                return CHtml::link($result['value'], array($modelName."/view", 'id'=>$model->$column));
            else
                return $result['value']." (Controller " . $result['object'] . " not generated)";
        } else
            return null;
    }

    public function getChildRelationships($model)
    {
        $valuestring = null;
        foreach($model->metaData->childRelationships as $index=>$child){
            $modelName = $model->schema->getObjectMapping($child['childSObject']);
            if($modelName != null){
                $valuestring .= CHtml::link("[".$child['childSObject']."] - ".$child['field'], array(
                                        strtolower($modelName)."/admin", 'field'=>$child['field'], 'value'=>$model->Id))."    (cascade delete = ". var_export($child['cascadeDelete'],true).")<br/>";
            }
            else
                $valuestring .= "[".$child['childSObject']."] - ".$child['field']."    (cascade delete = ". var_export($child['cascadeDelete'],true).")  (controller not generated)<br/>";
        }

        if($valuestring === null)
            return 'None';
        else
            return $valuestring;
    }


	/**
	 * Performs the AJAX validation.
	 * @param <?php echo $this->modelClass; ?> $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='<?php echo $this->class2id($this->modelClass); ?>-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

}
