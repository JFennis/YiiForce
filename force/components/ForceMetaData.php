<?php
/**
 * @author Joe Fennis (fennis.joe@gmail.com)
 * @version 0.1
 */

/**
 * The ForceMetaData maintains a copy of Force.com metadata for a specific sObject.
 * The object is strored in cache and provides details of an sObject in the Force.com org specified in the connection.
 *
 */
class ForceMetaData extends CComponent
{
    public $objectName;
    public $objectSchema=array(); //the complete schema as returned by Force.com

    public $fields=array();  //List of fields in the sObject
    public $childRelationships=array();  //The child relationships for the sObject

    public $labels=array();
    public $defaultValues=array();

    public $nameField;  //each sObject as a designated 'Name' field

	public function __construct($connection, $objectClass)
	{
	    $result = $connection->describe($objectClass);
        if(!$result['status'])
            return;

        $this->objectSchema = $result['message'];
        $this->objectName = $this->objectSchema['name'];

        foreach($this->objectSchema['fields'] as $field) {
            if(!$field['deprecatedAndHidden'])
                $this->fields[$field['name']] = $field;
        }

        foreach($this->objectSchema['childRelationships'] as $index=>$relation) {
            if(!$relation['deprecatedAndHidden'])
                $this->childRelationships[$index] = $relation;
        }

        foreach($this->fields as $name=>$field) {
            $this->defaultValues[$name] = $field['defaultValue'];
            $this->labels[$name] = $field['label'];
            if($field['nameField'])
                $this->nameField = $name;
        }
    }

    /**
     * Get the Force.com field label for the specified atribute
     * @param $field(string) the sObject fieldname
     * @return string
     */
    public function getAttributeLabel($field)
    {
        if(isset($this->labels[$field]))
            return $this->labels[$field];
        else
            return NULL;
    }

    /**
     * Get the Force.com field property value for the specified field property
     * @param $field(string) the sObject fieldname
     * @param $property(string) the field property
     * @return string
     */
    public function getFieldProperty($field, $property)
    {
        return $this->fields[$field][$property];
    }

    /**
     * Returns the object's metadata in array format suitable for CTreeView
     * @return array for CTreeView
     */
    public function getTreeView()
    {
	    $dataTree=array(
		    array(
			    'text'=>$this->objectName,
			    'children'=>$this->getTreeChildren($this->objectSchema, 'root'),
		    )
	    );
        return $dataTree;
    }

    /**
     * Recursive function to create the child nodes of metadata
     * @param $nodes(array) the array of data to convert to tree nodes
     * @param $nodeId(string) the name of the array passed for processing
     * @return array of tree nodes
     */
    private function getTreeChildren($nodes, $nodeId)
    {
        $children=array();
        foreach($nodes as $index=>$node){

            if($nodeId === 'fields')
                $text = $node['name'];
            else if ($nodeId === 'childRelationships')
                $text = $node['childSObject']." -> ".$node['field'];
            else if ($nodeId === 'picklistValues')
                $text = $node['label'];
            else
                $text = $index;

            if(!is_array($node))
                $children[] = array('text'=>$index." = ".CPropertyValue::ensureString($node));
            else
                $children[] = array('text'=>$text, 'children'=>$this->getTreeChildren($node, $index));
        }
        return $children;
    }
}
?>