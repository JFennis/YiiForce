<?php
/**
 * @author Joe Fennis (fennis.joe@gmail.com)
 * @version 0.1
 */

/**
 * This is a copy of the CActiveDataProvider class with some small modification to make it work 
 * with the ForceCriteria, ForceObject and ForceSort
 */
class ForceDataProvider extends CDataProvider
{
	/**
	 * @var string the primary ForceObject class name. The {@link getData()} method
	 * will return a list of objects of this class.
	 */
	public $modelClass;
	/**
	 * @var ForceObject
	 */
	public $model;
	/**
	 * @var string the name of key attribute for {@link modelClass}. If not set,
	 * it means the primary key of the corresponding database table will be used.
	 */
    public $keyAttribute;
	/**
	 * @var ForceCriteria
	 */
	private $_criteria;
	/**
	 * @var ForceCriteria
	 */
	private $_countCriteria;
	/**
	 * @var ForceSort
	 */
    private $_sort;

	/**
	 * Constructor.
	 * @param mixed $modelClass the model class (e.g. 'Post') or the model finder instance
	 * @param array $config configuration (name=>value) to be applied as the initial property values of this class.
	 */
	public function __construct($modelClass, $config=array())
	{
		if(is_string($modelClass))
		{
			$this->modelClass = $modelClass;
			$this->model = $this->getModel($this->modelClass);
		}
		elseif($modelClass instanceof ForceObject)
		{
			$this->modelClass = get_class($modelClass);
			$this->model = $modelClass;
		}
		$this->setId(CHtml::modelName($this->model));
		foreach($config as $key=>$value)
			$this->$key = $value;
	}

	/**
	 * Returns the query criteria.
	 * @return ForceCriteria the query criteria
	 */
	public function getCriteria()
	{
		if($this->_criteria===null)
			$this->_criteria = $this->model->searchCriteria();
		return $this->_criteria;
	}

	/**
	 * Sets the query criteria.
	 * @param ForceCriteria|array $value the query criteria. This can be either a ForceCriteria object or an array
	 * representing the query criteria.
	 */
	public function setCriteria($value)
	{
		$this->_criteria = $value instanceof ForceCriteria ? $value : $this->model->searchCriteria();
	}

	/**
	 * Returns the query criteria.
	 * @return ForceCriteria the query criteria
	 */
	public function getCountCriteria()
	{
		if($this->_countCriteria===null)
			$this->_countCriteria = $this->model->searchCriteria();
		return $this->_countCriteria;
	}

	/**
	 * Sets the query criteria.
	 * @param ForceCriteria|array $value the query criteria. This can be either a ForceCriteria object or an array
	 * representing the query criteria.
	 */
	public function setCountCriteria($value)
	{
		$this->_countCriteria = $value instanceof ForceCriteria ? $value : $this->model->searchCriteria();
	}

	/**
	 * Returns the sorting object.
	 * @param string $className the sorting object class name.
	 * @return ForceSort the sorting object.
	 */
	public function getSort($className = 'ForceSort')
	{
		if(($sort = parent::getSort($className)) !== false)
			$sort->modelClass = $this->modelClass;

		return $sort;
	}

	/**
	 * Given sObject class name returns new model instance.
	 * @param string $className active record class name.
	 * @return ForceObject model instance.
	 */
	protected function getModel($className)
	{
        $object = new $className;
		return $object;
	}

	/**
	 * Fetches the data from the Force.com.
	 * @return array list of ForceObjects
	 */
	public function fetchData()
	{
        $criteria = $this->criteria;

		if(($pagination = $this->getPagination()) !== false)
		{
			$pagination->setItemCount($this->getTotalItemCount());
			$pagination->applyLimit($criteria);
		}

		if(($sort = $this->getSort()) !== false)
		{
			$sort->applyOrder($criteria);
		}

		$data = $this->model->getSchema()->findAll($this->modelClass, $criteria);
        if(isset($data['status']))
            throw new CException($data['message']);
        else
            return $data;
	}

	/**
	 * Fetches the data item keys from Force.com.
	 * @return array list of data item keys.
	 */
	public function fetchKeys()
	{
		$keys = array();
		foreach($this->getData() as $i=>$data)
		{
			$key = $this->keyAttribute === null ? $data->getPrimaryKey() : $data->{$this->keyAttribute};
			$keys[$i] = is_array($key) ? implode(',',$key) : $key;
		}
		return $keys;
	}

	/**
	 * Calculates the total number of data items.
	 * @return integer the total number of data items.
	 */
	public function calculateTotalItemCount()
	{
		$count = $this->model->getSchema()->count($this->modelClass, $this->countCriteria);
		return $count;
	}
}
