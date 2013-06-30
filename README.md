#Yii Force

...is a module for the Yii PHP framework that allows the user to generate Models and CRUD Controllers/Views for Force.com sObjects. CRUD operations utilise the Force.com REST API, and a
cache should be configured to prevent excessive API calls (see Install instructions below).

##Note:
This module provides gii code generation for standard CRUD actions on Force.com sObjects. The SOQL Query builder works for the standard generated MVC files but still requires development and testing for more
advanced query options. 

##Requirements
* Yii Framework 1.1 or above
* PHP 5.3 or above


##Install Instructions:
1. Add the 'force' module to Yii by placing it in your application's module folder (for example '/protected/modules').

2. Configure a remote application in your Force.com Org as shown at https://help.salesforce.com/help/doc/en/remoteaccess_define.htm.

3. Edit your applications main.php config file and add the configuration for the force module
~~~PHP
    'modules'=>array(

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
    ),
~~~    		

4. Configure a cache in the main.php components section as the example shown below
~~~PHP
        'forceCache'=>array(
            'class'=>'system.caching.CDbCache',
            'connectionID'=>'db',
            'autoCreateCacheTable'=>TRUE,
        ),
~~~

5. Navigate to the force module http://your-yii-site/index.php/force.

6. Use the Force.com gii to generate the Model and CRUD Controllers and Views.


##Tips:
* You may need to update the gii ipFilters if you are not hosting your application locally, in which case you may see Error 403 - You are not allowed to access this page.
 You can go to http://www.whatismyip.com/ to get you current ip address. 
