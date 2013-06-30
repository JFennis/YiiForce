<?php
/**
 * @author Joe Fennis (fennis.joe@gmail.com)
 * @version 0.1
 */

/**
 * This is a copy of the CSort class with small modifications to work with sorting Force.com objects
 */

class ForceSort extends CComponent
{
	/**
	 * Sort ascending
	 * @since 1.1.10
	 */
	const SORT_ASC = false;

	/**
	 * Sort descending
	 * @since 1.1.10
	 */
	const SORT_DESC = true;

	/**
	 * @var boolean whether the sorting can be applied to multiple attributes simultaneously.
	 * Defaults to false, which means each time the data can only be sorted by one attribute.
	 */
	public $multiSort=false;
	/**
	 * @var string the name of the model class whose attributes can be sorted.
	 * The model class must be a child class of {@link CActiveRecord}.
	 */
	public $modelClass;
	/**
	 * @var array list of attributes that are allowed to be sorted.
	 * For example, array('user_id','create_time') would specify that only 'user_id'
	 * and 'create_time' of the model {@link modelClass} can be sorted.
	 * By default, this property is an empty array, which means all attributes in
	 * {@link modelClass} are allowed to be sorted.
	 */
	public $attributes=array();
	/**
	 * @var string the name of the GET parameter that specifies which attributes to be sorted
	 * in which direction. Defaults to 'sort'.
	 */
	public $sortVar='sort';
	/**
	 * @var string the tag appeared in the GET parameter that indicates the attribute should be sorted
	 * in descending order. Defaults to 'desc'.
	 */
	public $descTag='desc';
	/**
	 * @var mixed the default order that should be applied to the query criteria when
	 * the current request does not specify any sort. For example, 'name, create_time DESC' or
	 * 'UPPER(name)'.
	 *
	 */
	public $defaultOrder;
	/**
	 * @var string the route (controller ID and action ID) for generating the sorted contents.
	 * Defaults to empty string, meaning using the currently requested route.
	 */
	public $route='';
	/**
	 * @var array separators used in the generated URL. This must be an array consisting of
	 * two elements. The first element specifies the character separating different
	 * attributes, while the second element specifies the character separating attribute name
	 * and the corresponding sort direction. Defaults to array('-','.').
	 */
	public $separators=array('-','.');
	/**
	 * @var array the additional GET parameters (name=>value) that should be used when generating sort URLs.
	 * Defaults to null, meaning using the currently available GET parameters.
	 */
	public $params;

	private $_directions;


	/**
	 * Constructor.
	 * @param string $modelClass the class name of data models that need to be sorted.
	 * This should be a child class of {@link CActiveRecord}.
	 */
	public function __construct($modelClass=null)
	{
		$this->modelClass = $modelClass;
	}

	/**
	 * Modifies the query criteria by changing its {@link ForceCriteria::order} property.
	 * This method will use {@link directions} to determine which columns need to be sorted.
	 * They will be put in the ORDER BY clause. If the criteria already has non-empty {@link ForceCriteria::order} value,
	 * the new value will be appended to it.
	 * @param ForceCriteria $criteria the query criteria
	 */
	public function applyOrder($criteria)
	{
		$order = $this->getOrderBy($criteria);
		if(!empty($order))
		{
			if(!empty($criteria->order))
				$criteria->order .= ', ';
			$criteria->order .= $order;
		}
	}

	/**
	 * @param ForceCriteria $criteria the query criteria
	 * @return string the order-by columns represented by this sort object.
	 * This can be put in the ORDER BY clause of a SQL statement.
	 * @since 1.1.0
	 */
	public function getOrderBy($criteria=null)
	{
		$directions = $this->getDirections();

		if(empty($directions))
			return is_string($this->defaultOrder) ? $this->defaultOrder : '';
		else
		{
			$orders = array();
			foreach($directions as $attribute=>$descending)
			{
				$definition = $this->resolveAttribute($attribute);
				if(is_array($definition))
				{
					if($descending)
						$orders[] = isset($definition['desc']) ? $definition['desc'] : $attribute.' DESC';
					else
						$orders[] = isset($definition['asc']) ? $definition['asc'] : $attribute;
				}
				elseif($definition !== false)
				{
					$attribute = $definition;
					$orders[] = $descending?$attribute.' DESC':$attribute;
				}
			}
			return implode(', ',$orders);
		}
	}

	/**
	 * Generates a hyperlink that can be clicked to cause sorting.
	 * @param string $attribute the attribute name. This must be the actual attribute name, not alias.
	 * @param array $htmlOptions additional HTML attributes for the hyperlink tag
	 * @return string the generated hyperlink
	 */
	public function link($attribute,$label=null,$htmlOptions=array())
	{
		if($label === null)
			$label = $this->resolveLabel($attribute);

		if(($definition = $this->resolveAttribute($attribute)) === false)
			return $label;

		$directions = $this->getDirections();
		if(isset($directions[$attribute]))
		{
			$class = $directions[$attribute] ? 'desc' : 'asc';
			if(isset($htmlOptions['class']))
				$htmlOptions['class'].=' '.$class;
			else
				$htmlOptions['class']=$class;

			$descending =! $directions[$attribute];
			unset($directions[$attribute]);
		}
		elseif(is_array($definition) && isset($definition['default']))
			$descending = $definition['default']==='desc';
		else
			$descending=false;

		if($this->multiSort)
			$directions = array_merge(array($attribute=>$descending),$directions);
		else
			$directions=array($attribute=>$descending);

		$url = $this->createUrl(Yii::app()->getController(),$directions);

		return $this->createLink($attribute,$label,$url,$htmlOptions);
	}

	/**
	 * Resolves the attribute label for the specified attribute.
	 * This will invoke {@link ForceCriteria::getAttributeLabel} to determine what label to use.
	 * If the attribute refers to a virtual attribute declared in {@link attributes},
	 * then the label given in the {@link attributes} will be returned instead.
	 * @param string $attribute the attribute name.
	 * @return string the attribute label
	 */
	public function resolveLabel($attribute)
	{
		$definition = $this->resolveAttribute($attribute);
		if(is_array($definition))
		{
			if(isset($definition['label']))
				return $definition['label'];
		}
		elseif(is_string($definition))
			$attribute = $definition;

		if($this->modelClass !== null)
			return $this->getModel($this->modelClass)->getAttributeLabel($attribute);
		else
			return $attribute;
	}

	/**
	 * Returns the currently requested sort information.
	 * @return array sort directions indexed by attribute names.
	 * Sort direction can be either ForceSort::SORT_ASC for ascending order or
	 * ForceSort::SORT_DESC for descending order.
	 */
	public function getDirections()
	{
		if($this->_directions === null)
		{
			$this->_directions = array();
			if(isset($_GET[$this->sortVar]) && is_string($_GET[$this->sortVar]))
			{
				$attributes = explode($this->separators[0],$_GET[$this->sortVar]);
				foreach($attributes as $attribute)
				{
					if(($pos = strrpos($attribute,$this->separators[1])) !== false)
					{
						$descending = substr($attribute,$pos+1) === $this->descTag;
						if($descending)
							$attribute = substr($attribute,0,$pos);
					}
					else
						$descending = false;

					if(($this->resolveAttribute($attribute)) !== false)
					{
						$this->_directions[$attribute] = $descending;
						if(!$this->multiSort)
							return $this->_directions;
					}
				}
			}
			if($this->_directions === array() && is_array($this->defaultOrder))
				$this->_directions = $this->defaultOrder;
		}
		return $this->_directions;
	}

	/**
	 * Returns the sort direction of the specified attribute in the current request.
	 * @param string $attribute the attribute name
	 * @return mixed Sort direction of the attribute. Can be either ForceSort::SORT_ASC
	 * for ascending order or ForceSort::SORT_DESC for descending order. Value is null
	 * if the attribute doesn't need to be sorted.
	 */
	public function getDirection($attribute)
	{
		$this->getDirections();
		return isset($this->_directions[$attribute]) ? $this->_directions[$attribute] : null;
	}

	/**
	 * Creates a URL that can lead to generating sorted data.
	 * @param CController $controller the controller that will be used to create the URL.
	 * @param array $directions the sort directions indexed by attribute names.
	 * The sort direction can be either ForceSort::SORT_ASC for ascending order or
	 * ForceSort::SORT_DESC for descending order.
	 * @return string the URL for sorting
	 */
	public function createUrl($controller,$directions)
	{
		$sorts = array();
		foreach($directions as $attribute=>$descending)
			$sorts[] = $descending ? $attribute.$this->separators[1].$this->descTag : $attribute;

		$params = $this->params === null ? $_GET : $this->params;
		$params[$this->sortVar] = implode($this->separators[0],$sorts);
		return $controller->createUrl($this->route,$params);
	}

	/**
	 * Returns the real definition of an attribute given its name.
	 *
	 * The resolution is based on {@link attributes} and {@link ForceObject::attributeNames}.
	 * <ul>
	 * <li>When {@link attributes} is an empty array, if the name refers to an attribute of {@link modelClass},
	 * then the name is returned back.</li>
	 * <li>When {@link attributes} is not empty, if the name refers to an attribute declared in {@link attributes},
	 * then the corresponding virtual attribute definition is returned. Starting from version 1.1.3, if {@link attributes}
	 * contains a star ('*') element, the name will also be used to match against all model attributes.</li>
	 * <li>In all other cases, false is returned, meaning the name does not refer to a valid attribute.</li>
	 * </ul>
	 * @param string $attribute the attribute name that the user requests to sort on
	 * @return mixed the attribute name or the virtual attribute definition. False if the attribute cannot be sorted.
	 */
	public function resolveAttribute($attribute)
	{
		if($this->attributes !== array())
			$attributes = $this->attributes;
		elseif($this->modelClass !== null)
			$attributes = $this->getModel($this->modelClass)->attributeNames();
		else
			return false;

		foreach($attributes as $name=>$definition)
		{
			if(is_string($name))
			{
				if($name === $attribute)
					return $definition;
			}
			elseif($definition === '*')
			{
				if($this->modelClass !== null && $this->getModel($this->modelClass)->hasAttribute($attribute))
					return $attribute;
			}
			elseif($definition === $attribute)
				return $attribute;
		}
		return false;
	}

	/**
	 * Given active record class name returns new model instance.
	 *
	 * @param string $className active record class name.
	 * @return ForceModel active record model instance.
	 *
	 * @since 1.1.14
	 */
	protected function getModel($className)
	{
		return new $className;
	}

	/**
	 * Creates a hyperlink based on the given label and URL.
	 * You may override this method to customize the link generation.
	 * @param string $attribute the name of the attribute that this link is for
	 * @param string $label the label of the hyperlink
	 * @param string $url the URL
	 * @param array $htmlOptions additional HTML options
	 * @return string the generated hyperlink
	 */
	protected function createLink($attribute,$label,$url,$htmlOptions)
	{
		return CHtml::link($label,$url,$htmlOptions);
	}
}