Droctrine Mongo for Drupal 7.x
========================

Adds Doctrine MongoDB ODM Services to Drimple for use in Drupal 7.x. 
 
Requirements
--------------------------------

* Drupal 7.x
* PHP 5.3.2+
* Drimple (https://github.com/korstiaan/drimple)
* Doctrine MongoDB ODM (https://github.com/doctrine/mongodb-odm)

Doctrine MongoDB ODM availability, including its dependencies, can be achieved using Composer Loader (https://github.com/korstiaan/composer_loader) by adding the following line to your composer.json:

``` json
{
	"require": {
	    "doctrine/mongodb-odm": "dev-master"
	}
}
```

Autoloading
--------------------------------

Suggested is using nsautoload (https://github.com/korstiaan/nsautoload) for autoloading your documents and the service provider.

Installation
--------------------------------

Install it as a normal Drupal module. This means downloading (or git clone'ing) it to site/all/modules and enabling it on "admin/modules/list".
(If you're using voiture (http://voiture.hoppinger.com) just add "droctrine_mongo" to cnf/shared/modules.php)


Configuration
--------------------------------

Implement hook_hook_drimple_provide(\Drimple\Drimple $drimple) and register the service provider:

```php
<?php
// sites/all/modules/foo/foo.module

function foo_drimple_provide(\Drimple\Drimple $drimple)
{
	$drimple->register(
		new \DroctrineMongo\Provider\DoctrineMongoDBProvider(), 
		array(
			'doctrine.odm.mongodb.config.connection' => array(
				'connections'	=> array(
					'default'	=> array(
						'server'	=>  'mongodb://localhost:27017',
						'options'	=> array(
							'connect'	=> true,
						),
					),
				),
				'default'	=> 'default',
			),
			'doctrine.odm.mongodb.config.manager' => array(
				'managers'	=> array(
					'default'	=> array(
						'auto_mapping'	=> true,
					),
				),
				'config'	=> array(
					'proxy_dir'				=> file_directory_temp(),
					'hydrator_dir'			=> file_directory_temp(),
					'database'				=> 'db_mongo',
				),
				'default'	=> 'default',
			),
		)
	);
}
```

This will add the document manager for the default manager into the `doctrine.odm.mongodb.dm` service.

(See DoctrineMongoDBProvider::loadDocumentManagers and DoctrineMongoDBProvider::loadConnections phpdocs for more info and the other services it defines)

Usage
--------------------------------

Documents are looked for in every modules subdir `ModuleName/Document` with namespace `ModuleName\Document`. (Base namespace of a module is based on an underscore to CamelCase conversion) 

```php

<?php
// sites/all/modules/foo_bar/FooBar/Document/User.php

namespace FooBar\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class User
{
	/**
	 * @MongoDB\Id
	 */
	protected $id;
}

```

```php

$dm 	= \Drimple\Drimple::getInstance()->get('doctrine.odm.mongodb.dm');

$user 	= new \FooBar\Document\User();

$dm->persist($user);
$dm->flush();

``` 


Limitations
--------------------------------

* For now only the annotation driver is supported.
