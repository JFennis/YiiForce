<?php
    /**
 * @author Joe Fennis (fennis.joe@gmail.com)
 * @version 0.1
 */

/**
 * This is a combination of the CDbCommandBuilder and CDbCommand classes with modifications to build and bind a Force.com SOQL Query
 */

class ForceSOQLBuilder extends CComponent
{
    private $_connection;

	/**
	 * @param ForceConnection $connection
	 */
	public function __construct($connection)
	{
		$this->_connection = $connection;
	}

	/**
	 * Creates a query criteria.
	 * @param mixed $condition query condition or criteria.
	 * If a string, it is treated as query condition (the WHERE clause);
	 * If an array, it is treated as the initial values for constructing a {@link ForceCriteria} object;
	 * Otherwise, it should be an instance of {@link ForceCriteria}.
	 * @param array $params parameters to be bound to an SQL statement.
	 * This is only used when the first parameter is a string (query condition).
	 * In other cases, please use {@link ForceCriteria::params} to set parameters.
	 * @return ForceCriteria the created query criteria
	 */
	public function createCriteria($condition='',$params=array())
	{
		if(is_array($condition))
			$criteria = new ForceCriteria($condition);
		else if($condition instanceof ForceCriteria)
			$criteria = clone $condition;
		else
		{
			$criteria = new ForceCriteria;
			$criteria->condition = $condition;
			$criteria->params = $params;
		}
		return $criteria;
	}

	/**
	 * Creates a SELECT command for a single sObject.
	 * @param string $objectName the sObjectName.
	 * @param ForceCriteria $criteria the query criteria
	 * @return string SOQL query with bound condition parameters.
	 */
	public function createFindCommand($objectName, $criteria)
	{

        $fields = $this->_connection->getObjectMetaData($objectName)->fields;
		$select = is_array($criteria->select) ? implode(', ',$criteria->select) : $criteria->select;

		if($select==='*')
		{
            $select = implode(',',array_keys($fields));
		}

		$sql = ($criteria->distinct ? 'SELECT DISTINCT':'SELECT')." {$select} FROM {$objectName}";
		$sql = $this->applyJoin($sql,$criteria->join);
		$sql = $this->applyCondition($sql,$criteria->condition);
		$sql = $this->applyGroup($sql,$criteria->group);
		$sql = $this->applyHaving($sql,$criteria->having);
		$sql = $this->applyOrder($sql,$criteria->order);
		$sql = $this->applyLimit($sql,$criteria->limit,$criteria->offset);

        return $this->bindValues($fields, $sql, $criteria);
	}

	/**
	 * Alters the SQL to apply JOIN clause.
	 * @param string $sql the SQL statement to be altered
	 * @param string $join the JOIN clause (starting with join type, such as INNER JOIN)
	 * @return string the altered SQL statement
	 */
	public function applyJoin($sql,$join)
	{
		if($join!='')
			return $sql.' '.$join;
		else
			return $sql;
	}

	/**
	 * Alters the SQL to apply WHERE clause.
	 * @param string $sql the SQL statement without WHERE clause
	 * @param string $condition the WHERE clause (without WHERE keyword)
	 * @return string the altered SQL statement
	 */
	public function applyCondition($sql,$condition)
	{
		if($condition!='')
			return $sql.' WHERE '.$condition;
		else
			return $sql;
	}

	/**
	 * Alters the SQL to apply ORDER BY.
	 * @param string $sql SQL statement without ORDER BY.
	 * @param string $orderBy column ordering
	 * @return string modified SQL applied with ORDER BY.
	 */
	public function applyOrder($sql,$orderBy)
	{
		if($orderBy!='')
			return $sql.' ORDER BY '.$orderBy;
		else
			return $sql;
	}

	/**
	 * Alters the SQL to apply LIMIT and OFFSET.
	 * Default implementation is applicable for PostgreSQL, MySQL and SQLite.
	 * @param string $sql SQL query string without LIMIT and OFFSET.
	 * @param integer $limit maximum number of rows, -1 to ignore limit.
	 * @param integer $offset row offset, -1 to ignore offset.
	 * @return string SQL with LIMIT and OFFSET
	 */
	public function applyLimit($sql,$limit,$offset)
	{
		if($limit>=0)
			$sql.=' LIMIT '.(int)$limit;
		if($offset>0)
			$sql.=' OFFSET '.(int)$offset;
		return $sql;
	}

	/**
	 * Alters the SQL to apply GROUP BY.
	 * @param string $sql SQL query string without GROUP BY.
	 * @param string $group GROUP BY
	 * @return string SQL with GROUP BY.
	 */
	public function applyGroup($sql,$group)
	{
		if($group!='')
			return $sql.' GROUP BY '.$group;
		else
			return $sql;
	}

	/**
	 * Alters the SQL to apply HAVING.
	 * @param string $sql SQL query string without HAVING
	 * @param string $having HAVING
	 * @return string SQL with HAVING
	 */
	public function applyHaving($sql,$having)
	{
		if($having!='')
			return $sql.' HAVING '.$having;
		else
			return $sql;
	}

	/**
	 * Binds parameter values for an SOQL command.
	 * @param array $fields list of sObject fields
	 * @param string $sql the query statement with place holders
     * @param ForceCriteria $criteria the 
	 */
    public function bindValues($fields, $sql, $criteria)
    {

        $types=array();
        foreach($fields as $index=>$field){
            $types[$field['name']] = $field['type'];
        }

        $query = $sql;
        foreach($criteria->params as $tag=>$value) {
            $field = $criteria->conditionFields[$tag];
            switch ($types[$field]) {
                case 'id':
                    $value = " '".$value."' ";
                    break;
                case 'boolean':
                    $value = CPropertyValue::ensureBoolean($value);
                    if(!$value) $value='false'; else $value='true';
                    break;
               case 'reference':
                    $value = " '".$value."' ";
                    break;
                case 'string':
                    $value = " '".$value."' ";
                    break;
                case 'encryptedstring':
                    $value = " '".$value."' ";
                    break;
                case 'picklist':
                    $value = " '".$value."' ";
                    break;
                case 'url':
                    $value = " '".$value."' ";
                    break;
                case 'email':
                    $value = " '".$value."' ";
                    break;
                case 'textarea':
                    $value = " '".$value."' ";
                    break;
                case 'datetime':
                yii::trace('tag '. $tag . ' value ' . $value . ' SQL '.$sql, 'info');
                    if(strlen($value)>10)
                        $value = date_format(DateTime::createFromFormat('Y-m-d H:i:s', $value, new DateTimeZone('UTC')), 'c');
                    else
                        $value = date_format(DateTime::createFromFormat('Y-m-d', $value, new DateTimeZone('UTC')), 'c');
                    break;
                case 'date':
                    if(strlen($value)>10)
                        $value = date_format(DateTime::createFromFormat('Y-m-d H:i:s', $value, new DateTimeZone('UTC')), 'c');
                    else
                        $value = date_format(DateTime::createFromFormat('Y-m-d', $value, new DateTimeZone('UTC')), 'c');
                    break;
                case 'time':
                    $value = date_format(DateTime::createFromFormat('H:i:s', $value, new DateTimeZone('UTC')), 'c');
                    break;
            }
            $query = str_replace($tag, $value, $query);
        }
        return $query;
    }
}
?>