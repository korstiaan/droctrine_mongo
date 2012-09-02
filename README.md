# Droctrine Mongo for Drupal 7.x

Adds Doctrine MongoDB ODM Services to Drimple for use in Drupal 7.x. 

[![Build Status](https://secure.travis-ci.org/korstiaan/droctrine_mongo.png?branch=master)](http://travis-ci.org/korstiaan/droctrine_mongo)
 
## Requirements

* Drupal 7.x
* PHP 5.3.3+
* [Drimple](https://github.com/korstiaan/drimple)
* [Doctrine MongoDB ODM](https://github.com/doctrine/mongodb-odm)

## Installation

The recommended way to install `Droctrine Mongo` is with [Composer](http://getcomposer.org). 
Just add the following to your `composer.json`:

```json
   {
       "minimum-stability": "dev",
       "require": {
              ...
           "korstiaan/droctrine-mongo": "dev-master"
       }
   }
```

Now update composer and install the newly added requirement and its dependencies (including `Drimple`):

``` bash
$ php composer.phar update korstiaan/droctrine-mongo
```

### Using Composer

Using `Composer` means including its autoloader. Add the following to your Drupals settings.php:

```php
// /path/to/sites/default/settings.php

require '/path/to/vendor/autoload.php';
```

## Configuration

Implement `hook_hook_drimple_provide(\Drimple\Drimple $drimple)` and register the service provider. For example:

```php
<?php
// sites/all/modules/foo/foo.module

function foo_drimple_provide(\Drimple\Drimple $drimple)
{
    $drimple->register(
        new \Droctrine\Mongo\Provider\DoctrineMongoDBProvider(), 
        array(
            'doctrine.odm.mongodb.config.connection' => array(
                'connections' => array(
                    'default' => array(
                        'server'  =>  'mongodb://localhost:27017',
                        'options' => array(
                            'connect' => true,
                        ),
                    ),
                ),
                'default' => 'default',
            ),
            'doctrine.odm.mongodb.config.manager' => array(
                'managers' => array(
                    'default' => array(
                        'auto_mapping' => true,
                    ),
                ),
                'config'   => array(
                    'proxy_dir'    => file_directory_temp(),
                    'hydrator_dir' => file_directory_temp(),
                    'database'     => 'db_mongo',
                ),
                'default' => 'default',
            ),
        )
    );
}
```

Effectively, this will add the document manager for the default manager with default connection into the `doctrine.odm.mongodb.dm` service.

    See `DoctrineMongoDBProvider::loadDocumentManagers` and `DoctrineMongoDBProvider::loadConnections` phpdocs for more configuration information and the other services it defines

## Usage

When setting _auto_mapping = true_ in the provider's configuration, documents are looked for in every module's subdir `ModuleName/Document` with namespace `ModuleName\Document`. (Base namespace of a module is based on an underscore to CamelCase conversion) 

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

Now if you want to persist this document, just:

```php

$dm   = drimple()->get('doctrine.odm.mongodb.dm');

$user = new \FooBar\Document\User();

$dm->persist($user);
$dm->flush();

```

Or if you want to fetch all documents:
 
```php

$dm   = drimple()->get('doctrine.odm.mongodb.dm');

$docs = $dm->getRepository('FooBar\\Document\\User')->findAll();

```

## Limitations

* For now only the annotation driver is supported.

## License

Droctrine Mongo is licensed under the MIT license.

