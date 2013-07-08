<?php
/**
 * @author Joe Fennis (fennis.joe@gmail.com)
 * @version 0.1
 */

/**
 * The ForceSchema maintains a copy of the general Force.com describe REST API.
 * The schema object is strored in cache and provides details of all sObjects in the Force.com org specified in the connection
 * The schema object is also used to perform queries against the Force.com database to retrieve sObject records and create
 * corresponding ForceObject instances.
 */
class ForceSchema
{
    public $name;
    public $connection;

    private $_objects=array();
    private $_modelMappings;

	public function __construct($connection, $name)
	{
        $this->name = $name;
        $this->connection = $connection;

        $result = $connection->describeAll();
        if(!$result['status'])
            throw new CDbException($result);

		$globalSchema = $result['message']; 
        foreach($globalSchema['sobjects'] as $index=>$object)
            $this->_objects[$object['name']] = $object;
    }

    /**
     * Get the details of all objects or the specified object if the name param provided
     * @param $name(string) the sObject name to retrieve
     * @return array of object metadata
     */
    public function getObjects($name = null)
    {
        if($name != null)
            if(isset( $this->_objects[$name]))
                return $this->_objects[$name];
            else
                return null;

        return $this->_objects;

    }

    /**
     * Used to obtain a list of sObject in the org.
     * @return array of sObject names index=>objectName
     */
    public function listObjects(){
        return array_keys($this->_objects);
    }

    /**
     * Returns a treeview of generated sObject models with metadata for each oject
     * @return array of sObject names index=>objectName
     */
    public function getTreeView()
    {
 		$path = Yii::getPathOfAlias('force.models');
		$names = scandir($path);

        $models = array();
		foreach($names as $name)
		{
            $pos = strpos($name, '.php', 1);
		    if($pos > 0){
		        $name = substr($name, 0, $pos);
                if(class_exists($name)){
                   $object = new $name;
                   $treeView = $object->metaData->getTreeView();
                   $link = CHtml::link(CHtml::encode($name), array('/force/'.strtolower($name)));
                   $models[] = array('text'=>$link, 'children'=>$treeView );                     
                }
		    }      
        }
		return $models;               
    }

   //Manage Yii model name mapping to force objects

    /**
     * Reads all the Models generated using the gii generated and builds a list of mappings
     * @return array of  modelName=>sObjectName mappings.
     */
    public function updateModelMappings()
    {
		$files = scandir(Yii::getPathOfAlias('force.models'));

        $mapping = array();
		foreach($files as $file)
		{
            if(($pos = strripos($file,'.php')) > 0){
                $objectName = substr($file,0,$pos);
                if(class_exists($objectName)){
                    $object = new $objectName;
                    $mapping[get_class($object)] = $object->sObject;                   
                }
            }
		}        
        $this->_modelMappings = $mapping;
    }

    /**
     * Get the sObject name for the specified ModelName
     * @param $model(string) is the model name to map.
     * @return string sObject name.
     */
    public function getModelMapping($model)
    {
        if($this->_modelMappings === null)
            $this->updateModelMappings();

        if(isset($this->_modelMappings[$model]))
            return $this->_modelMappings[$model];
        else
            return NULL;
    }

    /**
     * Get the Model name for the specified sObjectName
     * @param $objectName(string) is the sObject name to map.
     * @return string Model name.
     */
    public function getObjectMapping($objectName)
    {
        if($this->_modelMappings === null)
            $this->updateModelMappings();

        $objectMappings = array_flip($this->_modelMappings);
        if(isset($objectMappings[$objectName]))
            return $objectMappings[$objectName];
        else
            return NULL;
    }


    // create ForceObject instances from the Force.com org

    /**
     * Find all records for the specified sObjectName with the specified ForceCriteria condition or criteria parameters. 
     * If no condition or params specified then a select * will be performed.
     * @param $objectName(string) the sObject to query, optional $condition(ForceCriteria), 
     * optional $params parameters to be bound to an SOQL statement
     * @return array sObject records
     */
	public function findAll($objectName, $condition='',$params=array())
	{
        if($this->getModelMapping($objectName) === null)
            return NULL;

        $object = new $objectName;

        $cmdBuilder = new ForceSOQLBuilder($this->connection);
        $criteria = $cmdBuilder->createCriteria($condition,$params);
		$soql = $cmdBuilder->createFindCommand($object->sObject, $criteria);
 
        $records = $this->connection->executeSOQL($soql);
        if($records['status'])
            return $this->populateObjects($objectName, $records['message']);
        else
            return array('status'=>FALSE, 'message'=>$records['message']);
	}

    /**
     * Find a single record for the specified sObjectName with the specified Force.com Id. 
     * @param $objectName(string) the sObject to query, $pk(string) the Force.com Id 
     * @return array of sObject record
     */
    public function findByPk($objectName, $pk)
    {
        if($this->getModelMapping($objectName) === null)
            return NULL;

        $object = new $objectName;

        $record = $this->connection->sObjectShow($object->sObject, $pk);
        if($record['status'])
            return $this->populateObject($objectName, $record['message']);
        else
            return $record['message'];
    }

    /**
     * Get the count of records for the specified sObjectName with the specified ForceCriteria condition or criteria parameters. 
     * If no condition or params specified then a select * will be performed.
     * @param $objectName(string) the sObject to query, optional $condition(ForceCriteria), 
     * optional $params parameters to be bound to an SOQL statement
     * @return integer
     */
	public function count($objectName, $condition='',$params=array())
	{
        if($this->getModelMapping($objectName) === null)
            return NULL;

        $object = new $objectName;

        $cmdBuilder = new ForceSOQLBuilder($this->connection);
        $criteria = $cmdBuilder->createCriteria($condition,$params);
		$soql = $cmdBuilder->createFindCommand($object->sObject, $criteria);
        
        $records = $this->connection->executeSOQL($soql);
        if($records['status'])
            return $records['message']['totalSize'];
        else
            return $records['message'];
	}

    /**
     * Create and array of ForceObjects from the array of sObject records. 
     * @param $objectName(string) the objects to create (note this is the model name)
     * @param $data the array of sObject records, 
     * @return array of ForceObjects
     */
	public function populateObjects($objectName, $data)
	{
		$objects = array();
		foreach($data['records'] as $index=>$record)
		{
			if(($object = $this->populateObject($objectName, $record)) !== null)
			{
    			$objects[$index] = $object;
			}
		}
		return $objects;
	}
 
     /**
     * Create the specified ForceObject and copy values to properties. 
     * @param $objectName(string) the objects to create (note this is the model name)
     * @param $record the sObject record, 
     * @return ForceObject
     */
	public function populateObject($objectName, $record)
	{
		$object = new $objectName;
        $object->setAttributes($record);
        $object->isNewRecord = false;
		return $object;
	}
}
?>