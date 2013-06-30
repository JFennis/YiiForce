#Yii Force

...is a module for the Yii PHP framework allowing the user to generate Models and CRUD Controllers and Views for Force.com sObjects using the Force.com REST API.

##HINT:
CAUTION: This module provides gii code generation for Force.com sObjects. The SOQL Query builder works for the gii MVC files but still requires development and testing for more advanced query
options. 


##INSTALL:
1. Add the 'force' module to Yii by placing it in your application's module folder (for example '/protected/modules').

2. configure a remote application in your Force.com Org see https://help.salesforce.com/help/doc/en/remoteaccess_define.htm.

3. Edit your applications main.php config file and add the configuration for the force module
~~~
       'force'=>array(
            'modules'=>array(
             'gii'=>array(
			    'class'=>'force.modules.gii.GiiModuleForce',
			    'password'=>'yourPassword',
			    // If removed, Gii defaults to localhost only. Edit carefully to taste.
			    'ipFilters'=>array('127.0.0.1','::1'),
                ),
            ),           
            'components'=>array(
                'forceConnection'=>array(
                    'class'=>'force.components.ForceConnection',
                    'schemaName'=>'mySchema',     //any name will do here
                    'site'=>'https://login.salesforce.com',
                    'apiKey'=>'Force.com generated api key',
                    'secret'=>'Force.com generated secret',
                    'callbackUrl'=>'https://your-yii-site/index.php/force/default/oauthcallback',   //this must be a https url
                    'cacheID'=>'forceCache',   //a cache must be configured - see example below
                 ),
             ),
        ),
~~~    		

4. Configure a cache in the main.php components section as the example shown below
~~~
        'forceCache'=>array(
            'class'=>'system.caching.CDbCache',
            'connectionID'=>'db',
            'autoCreateCacheTable'=>TRUE,
        ),
~~~

5. Navigate to the force module http://your-yii-site/index.php/force.

6. Use the Force.com gii to generate the Model and CRUD Controllers and Views.


