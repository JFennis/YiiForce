<?php
/**
 * @author Joe Fennis (fennis.joe@gmail.com)
 * @version 0.1
 */

/**
 * The ForceObject represents an sObject record from the Force.com database. 
 * This is an abstract class for derived classes generated using the Force gii module.
 * The ForceSchema is used to create ForceObjects using the FindAll and FindByPK methods.
 *
 */

abstract class ForceObject extends CModel
{
	private $_new = true;
    protected $_connection;

	public function behaviors()
	{
		return array();
	}

	public function __construct($scenario='insert')
	{   
        $this->_connection = Yii::app()->getModule('force')->forceConnection;
        $this->scenario = $scenario;
	}

    /**
     * Getter
     * @return ForceConnection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Getter
     * @return ForceSchema
     */
    public function getSchema()
    {
        return $this->_connection->schema;
    }

    /**
     * Getter
     * @return ForceMetaData for this ForceObject. Note that the sObjectName is used and not the ModelName
     */
    public function getMetaData($fromCache=true)
    {
        return $this->connection->getObjectMetaData($this->sObject, $fromCache);
    }

    /**
     * Getter
     * @return boolean
     */
	public function getIsNewRecord()
	{
		return $this->_new;
	}

    /**
     * Setter
     */
	public function setIsNewRecord($value)
	{
		$this->_new = $value;
	}

    /**
     * Getter
     * @return Force.com sObject Id
     */
	public function getPrimaryKey()
	{
        return $this->Id;
	}

    /**
     * Getter
     * @return the Force.com designated Name field for this sObject
     */
    public function getNameField()
    {
        return $this->getMetaData()->nameField;
    }

    /**
     * IMPLEMENTATION FOR CModule abstract method
     * @return arra of field names index=>fieldName
     */
	public function attributeNames()
	{
        return array_keys($this->getMetaData()->fields);
	}

    /**
     * Check that field exists for this sObject
     * @return boolean
     */
	public function hasAttribute($name)
	{
		return property_exists($this, $name);
	}

    /**
     * Getter
     * @return the attribute label for the specified attribute(field)
     */
	public function getAttributeLabel($attribute)
	{
        if($this->metaData->getAttributeLabel($attribute) != NULL)
            return $this->metaData->getAttributeLabel($attribute);
		else
			return $this->generateAttributeLabel($attribute);
	}

    /**
     * Return the field names and corresponding values of fields that are updateable in Force.com
     * @return array of fieldName=>value
     */
    public function updateableAttributes()
    {
        $attributes = array();
        foreach($this->getMetaData()->fields as $name=>$properties) {
            if($properties['updateable']) {
                if($properties['type'] == 'boolean'){
                    $this->$name = CPropertyValue::ensureBoolean($this->$name);
                }
                $attributes[$name] = $this->$name;
            }
        } 
        return $attributes;
    }

    /**
     * Return the field names and corresponding values of fields that are createable in Force.com
     * @return array of fieldName=>value
     */
    public function createableAttributes()
    {
        $attributes = array();
        foreach($this->getMetaData()->fields as $name=>$properties) {
            if($properties['createable'] && isset($this->$name) && $this->$name != NULL) {
                if($properties['type'] == 'boolean'){
                    $this->$name = CPropertyValue::ensureBoolean($this->$name);
                }
                $attributes[$name] = $this->$name;
            }
        } 
        return $attributes;
    }

    //RELATIONSHIP METHODS

    /**
     * Return the list of reference data for a specified Reference field. 
     * Note that in Force.com an sObject field can have reference data from 1 to many related sObjects
     * @param $column the sObject field 
     * @return array of Id=>NameField
     */
    public function getReferenceData($column)
    {
        $field = $this->metaData->fields[$column];
        $objects = $field['referenceTo'];

        $references = array();
        foreach($objects as $index=>$objectClass){

            $nameField = $this->connection->getObjectMetaData($objectClass)->nameField;
            $query = "SELECT Id, ".$nameField." FROM ".$objectClass;

            $result = $this->connection->executeSOQL($query);
            if($result['status'] && $result['message']['totalSize'] > 0) {
                foreach($result['message']['records'] as $record)
                    if($record[$nameField] != NULL)
                        $references[$record['Id']] = $record[$nameField];
            }
        }

       //if($this->metaData->getFieldProperty($column,'nillable'))
            $references = array_merge(array(null=>' '), $references);

        return $references;
    }

    /**
     * Return the NameField value of the referenced sObject. 
     * Note that in Force.com an sObject field can have reference data from 1 to many related sObjects
     * @param $attribute the sObject field 
     * @return array parentObject=>value
     */
    public function getParentValue($attribute)
    {
        $field = $this->metaData->fields[$attribute];
        if($field['type'] != 'reference' || $this->$attribute === NULL || !isset($this->$attribute))
            return NULL;

        foreach($parents = $field['referenceTo'] as $index=>$parent){
            $nameField = $this->connection->getObjectMetaData($parent)->nameField;
            $query = "SELECT ".$nameField." FROM ".$parent." WHERE Id = '".$this->$attribute."'";
            $result = $this->connection->executeSOQL($query);
            if($result['status'] && $result['message']['totalSize'] === 1)
                return array('object'=>$parent, 'value'=>$result['message']['records'][0][$nameField]);
        }
        return NULL;
    }


    //CRUD methods

    /**
     * Delete this sObject from the Force.com database
     * @return error array or success string
     */
    public function delete()
    {
		if(!$this->getIsNewRecord()){
            $result = $this->_connection->sObjectDelete($this);
            if($result['status'])
                return TRUE;
            else
                return $result['message']; 
        }
		else
			return 'The record cannot be deleted because it is new.';
    }

    /**
     * Create of save this sObject in the Force.com database
     * @return error array or success string
     */
    public function save()  
    {
        if($this->getIsNewRecord()){
            $result = $this->_connection->sObjectCreate($this);
            if($result['status']){
                $this->Id = $result['message']['id'];
                return TRUE;
            } else
                return $result['message'];
        } else {
            $result = $this->_connection->sObjectSave($this);
            if($result['status'])
                return TRUE;
            else
                return $result['message'];
        }
    }
}
?>