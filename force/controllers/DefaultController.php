<?php

class DefaultController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout ='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
            'forceOauth + index', //only index will call Salesforce logon screen
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
				'actions'=>array('index', 'oauth', 'oauthcallback', 'test1', 'test2'),
				'users'=>array('*'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

    public function actionOauth()
	{
        $connection = Yii::app()->getModule('force')->forceConnection;
        $this->redirect($connection->oauth());;
	}

	public function actionOauthcallback()
	{
        $connection =  Yii::app()->getModule('force')->forceConnection;
        $connection->oauthCallback();
        $this->redirect(array('/force'));
	}

    public function actionIndex()
	{
        $this->render('index',array());
	}

    public function filterForceOauth( $c )
    {
        if(!isset(Yii::app()->session['access_token'])){
            $this->redirect(array('/force/default/oauth'));
        }
        $c->run(); 
    } 


	public function actionTest1()
	{
		$this->render('test1',array());
	}

	public function actionTest2()
	{
		$this->render('test2',array());
	}

    public function getForceControllers()
    {
		$path = Yii::getPathOfAlias('force.controllers');
		$names = scandir($path);

        $controllers = array();
		foreach($names as $name)
		{
            $pos = strpos($name, 'Controller', 1);
		    if($pos > 0){
		        $name = substr($name, 0, $pos);
                if($name!='Login' && $name!='Default')
                $controllers[] = strtolower($name); 
		    }      
        }
		return $controllers;        
    }
}