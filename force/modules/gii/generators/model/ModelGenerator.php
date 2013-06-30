<?php

class ModelGenerator extends CCodeGenerator
{
	public $codeModel='force.modules.gii.generators.model.ModelCode';

	/**
	 * Provides autocomplete table names
	 * @param string $db the database connection component id
	 * @return string the json array of tablenames that contains the entered term $q
	 */
	public function actionGetTableNames($db)
	{
		if(Yii::app()->getRequest()->getIsAjaxRequest())
		{
			$all = array();
			if(!empty($db) && Yii::app()->getModule('force')->hasComponent($db)!==false && (Yii::app()->getModule('force')->getComponent($db) instanceof ForceConnection))
				$all=array_keys(Yii::app()->getModule('force')->{$db}->getObjects());

			echo json_encode($all);
		}
		else
			throw new CHttpException(404,'The requested page does not exist.');
	}
}