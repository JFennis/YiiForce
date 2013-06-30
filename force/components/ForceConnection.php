<?php
/**
 * @author Joe Fennis (fennis.joe@gmail.com)
 * @version 0.1
 */

/**
 * The ForceConnection component manages the Force.com REST interface for authentication, describe and CRUD calls. 
 *It also manages the cache for schema and SOQL queries.
 */
class ForceConnection extends CApplicationComponent
{
    //set in main.php file
    public $site;   
    public $apiKey; 
    public $secret;  
    public $callbackUrl; 
    public $schemaName;
	public $cacheID;        //caching is required 

    //cache durations
    public $schemaCachingDuration=120;
    public $queryCachingDuration=30;

    private $_cache;
    private $_schema;

    public function init()
    {
        $this->_cache = Yii::app()->getComponent($this->cacheID);
    }

    //Manage chaching of global schema, object schemas and queries caching

    /**
     * Get the global schema definiton from Force.com
     * @param $fromCache flag. False will always initiate a new describe call
     * @return ForceSchema object
     */
    public function getSchema($fromCache=true)
    {
        $cacheKey = 'force.schema.'.$this->schemaName;

        if($fromCache && $this->_schema != null && isset($this->_cache[$cacheKey]))
            return $this->_schema;

        if($this->_cache === null)
            return $this->_schema = new ForceSchema($this, $this->schemaName);

        if(!$fromCache || !isset($this->_cache[$cacheKey])) 
            $this->_cache->set($cacheKey, new ForceSchema($this, $this->schemaName), $this->schemaCachingDuration);
        
        return $this->_schema = $this->_cache->get($cacheKey);       
    }

    /**
     * Get the schema definiton from Force.com for the specified sObject
     * @param $objectClass(string) name of the sObject to retrieve, $fromCache flag. False will always initiate a new describe call
     * @return ForceMetaData object
     */
    public function getObjectMetaData($objectClass, $fromCache=true)
    {
        $cacheKey = 'force.objectschema.'.$objectClass;

        if($this->_cache === null)
            return new ForceMetaData($this, $objectClass);

        if(!$fromCache || !isset($this->_cache[$cacheKey])) 
           $this->_cache->set($cacheKey, new ForceMetaData($this, $objectClass), $this->schemaCachingDuration);
             
        return $this->_cache->get($cacheKey);        
    }

    /**
     * Manage the caching of SOQL queries sent to Force.com
     * @param $soql(string) a SOQL query to execute, $fromCache flag. False will always initiate a new describe call
     * @return and array of ForceObject records
     */
    public function executeSOQL($soql, $fromCache=true)
    {
        $cacheKey='yii:forcequery'.':'.$this->schemaName.':'.$soql;

       if($this->_cache === null)
            return $this->query($soql);

        if(!$fromCache || !isset($this->_cache[$cacheKey])) 
		    $this->_cache->set($cacheKey, $this->query($soql), $this->queryCachingDuration);

        return $this->_cache->get($cacheKey);
    }

    //Manage Salesforce.com authentication

    /**
     * Calls the Salesforce.com login page
     */
    public function oauth()
	{
        return $this->site
            . "/services/oauth2/authorize?response_type=code&client_id="
            . $this->apiKey . "&redirect_uri=" . urlencode($this->callbackUrl);
	}

    /**
     * The callback function to obtain a valid oauth token and instance url.
     * The token and instance url are stored in the user session state.
     */
    public function oauthCallback() 
    {
        $token_url = $this->site . "/services/oauth2/token";

        $code = $_GET['code'];

        if (!isset($code) || $code == "") {
            Yii::app()->session['force'] = 'Missing Code';
            return FALSE;
        }

        $params = "code=" . $code
            . "&grant_type=authorization_code"
            . "&client_id=" . $this->apiKey
            . "&client_secret=" . $this->secret
            . "&redirect_uri=" . urlencode($this->callbackUrl);

        $curl = curl_init($token_url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $json_response = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ( $status != 200 ) {
            throw new CDbException("Error: call to token URL $token_url failed with status $status, response $json_response, curl_error "
                     . curl_error($curl) . ", curl_errno " . curl_errno($curl));
            Yii::app()->session['force'] = 'Status error - see log file';
            return FALSE;
        }

        curl_close($curl);

        $response = json_decode($json_response, true);

        $access_token = $response['access_token'];
        $instance_url = $response['instance_url'];

        if (!isset($access_token) || $access_token == "") {
            Yii::app()->session['force'] = 'error - see log file';
            return FALSE;
        }

        if (!isset($instance_url) || $instance_url == "") {
            Yii::app()->session['force'] = 'error - see log file';
            return false;
        }

        Yii::app()->session['access_token'] = $access_token;
        Yii::app()->session['instance_url'] = $instance_url;
        Yii::app()->session['force'] = 'Token';

        return;
    }

    //Describe rest calls for metadata

    /**
     * Call the Force.com REST API for describing all sObject in the Force.com org.
     * @return array
     */
    public function describeAll()
    {
      if(!isset(Yii::app()->session['access_token'])){
           Yii::app()->controller->redirect(array('/force/default/oauth'));
        }

        $access_token = Yii::app()->session['access_token'];
        $instance_url = Yii::app()->session['instance_url'];

        $url = "$instance_url/services/data/v26.0/sobjects/";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $data = json_decode($json_response, true);
        if($status != 200)
           $result = array('status'=>FALSE, 'message'=> "Error: call to URL $url failed with status $status, response $json_response, curl_error "
                     . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        else
            $result = array('status'=>TRUE, 'message'=>json_decode($json_response, true));

        curl_close($curl);
        return $result;
    }

    /**
     * Call the Force.com REST API for describing a specific sObject in the Force.com org.
     * @param $name of the required sObject
     * @return array
     */
    public function describe($name)
    {
        if(!isset(Yii::app()->session['access_token'])){
           Yii::app()->controller->redirect(array('/force/default/oauth'));
        }

        $access_token = Yii::app()->session['access_token'];
        $instance_url = Yii::app()->session['instance_url'];

        $url = "$instance_url/services/data/v20.0/sobjects/$name/describe/";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($status != 200)
            $result = array('status'=>FALSE, 'message'=> "Error: call to URL $url failed with status $status, response $json_response, curl_error "
                     . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        else
            $result = array('status'=>TRUE, 'message'=>json_decode($json_response, true));

        curl_close($curl);
        return $result;
    }


    //Object REST calls

    /**
     * Call the Force.com REST API for creating a new sObject in the Force.com org.
     * @param $object(ForceObject)
     * @return array with the Id of the new sObject
     */
    public function sObjectCreate($object)
    {
       if(!isset(Yii::app()->session['access_token'])){
           Yii::app()->controller->redirect(array('/force/default/oauth'));
        }

        $access_token = Yii::app()->session['access_token'];
        $instance_url = Yii::app()->session['instance_url'];

        $url = "$instance_url/services/data/v20.0/sobjects/$object->sObject/";
        
        $values = $object->createableAttributes();
        $content = json_encode($values);

        $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER,
                    array("Authorization: OAuth $access_token",
                        "Content-type: application/json"));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ( $status != 201 )
             $result = array('status'=>FALSE, 'message'=> "Error: call to URL $url failed with status $status, response $json_response, curl_error "
                     . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        else
            $result = array('status'=>TRUE, 'message'=>json_decode($json_response, true));
        
        curl_close($curl);
        return $result;
    }

    /**
     * Call the Force.com REST API for retrieving a specific instance of an sObject.
     * @param $objectName(string) the sObject name, $id the Force.com Id of the sObject to retrieve
     * @return array with sObject record
     */
    public function sObjectShow($objectName, $id)
    {

       if(!isset(Yii::app()->session['access_token'])){
           Yii::app()->controller->redirect(array('/force/default/oauth'));
        }

        $access_token = Yii::app()->session['access_token'];
        $instance_url = Yii::app()->session['instance_url'];

        $url = "$instance_url/services/data/v20.0/sobjects/$objectName/$id";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if($status != 200)
            $result = array('status'=>FALSE, 'message'=>"Error: call to URL $url failed with status $status, response $json_response, curl_error "
                     . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        else
            $result = array('status'=>TRUE, 'message'=>json_decode($json_response, true));
        
        curl_close($curl);
        return $result;
    }


    /**
     * Call the Force.com REST API for saving an existing sObject in the Force.com org.
     * @param $object(ForceObject)
     * @return array
     */
    public function sObjectSave($object)
    {
       if(!isset(Yii::app()->session['access_token'])){
           Yii::app()->controller->redirect(array('/force/default/oauth'));
        }

        $access_token = Yii::app()->session['access_token'];
        $instance_url = Yii::app()->session['instance_url'];

        $primaryKey = $object->getPrimaryKey();
        $url = "$instance_url/services/data/v24.0/sobjects/$object->sObject/$primaryKey";        
        $content = json_encode($object->updateableAttributes());

        $curl = curl_init($url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
                array("Authorization: OAuth $access_token",
                    "Content-type: application/json"));
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status != 204)
            $result = array('status'=>FALSE, 'message'=>"Error: call to URL $url failed with status $status, response $json_response, curl_error "
                     . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        else
            $result = array('status'=>TRUE, 'message'=>'Object saved');
        
        curl_close($curl);
        return $result;
    }

    /**
     * Call the Force.com REST API for deleteing an existing sObject record in the Force.com org.
     * @param $object(ForceObject)
     * @return array
     */
    public function sObjectDelete($object)
    {
       if(!isset(Yii::app()->session['access_token'])){
           Yii::app()->controller->redirect(array('/force/default/oauth'));
        }

        $access_token = Yii::app()->session['access_token'];
        $instance_url = Yii::app()->session['instance_url'];

        $primaryKey = $object->getPrimaryKey();
        $url = "$instance_url/services/data/v20.0/sobjects/$object->sObject/$primaryKey";
        $curl = curl_init($url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER,
                array("Authorization: OAuth $access_token"));
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($status != 204) 
           $result = array('status'=>FALSE, 'message'=>"Delete failed with status $status, response $json_response");
        else
            $result = array('status'=>TRUE, 'message'=>'Object deleted');

        curl_close($curl); 
        return $result;
    }

    //SOQL Rest calls
    
    /**
     * Call the Force.com REST API for submitting a SOQL query.
     * @param $query(string)
     * @return array with records
     */
    public function query($query)
    {
       if(!isset(Yii::app()->session['access_token'])){
           Yii::app()->controller->redirect(array('/force/default/oauth'));
        }

        $access_token = Yii::app()->session['access_token'];
        $instance_url = Yii::app()->session['instance_url'];

        $url = "$instance_url/services/data/v24.0/query/?q=" . urlencode($query);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($status != 200)
            $result = array('status'=>FALSE, 'message'=>"Error: call to URL $url failed with status $status, response $json_response, curl_error "
                                                            . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        else
            $result = array('status'=>TRUE, 'message'=>json_decode($json_response, true));

        curl_close($curl);
        return $result;  
    }
}