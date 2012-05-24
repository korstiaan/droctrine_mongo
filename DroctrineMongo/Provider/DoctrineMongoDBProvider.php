<?php
/**
 *  Droctrine Mongo
 *  Copyright (C) 2012  Korstiaan de Ridder <korstiaan [at] korstiaan.com>
 *
 *	This file is part of Droctrine Mongo
 *
 *  Droctrine Mongo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Droctrine Mongo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Droctrine Mongo.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace DroctrineMongo\Provider;

use 
	Doctrine\Common\EventManager,
	Doctrine\Common\Annotations\AnnotationReader,
	Doctrine\ODM\MongoDB\Configuration,
	Doctrine\ODM\MongoDB\DocumentManager,
	Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
	Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain,
	Doctrine\MongoDB\Connection
;

use 
	Drimple\Drimple,
	Drimple\Provider\ServiceProviderInterface
;

class DoctrineMongoDBProvider implements ServiceProviderInterface
{
	/**
	 * (non-PHPdoc)
	 * @see Drimple\Provider.ServiceProviderInterface::register()
	 */
	public function register(Drimple $drimple)
	{		
		/**
		 * Add connection services definition from $drimple[doctrine.odm.mongodb.config.connection]
		 */
		$this->loadConnections($drimple, 		$drimple['doctrine.odm.mongodb.config.connection']);
		
		/**
		 * Add document manager services definition from $drimple[doctrine.odm.mongodb.config.manager]
		 */
		$this->loadDocumentManagers($drimple, 	$drimple['doctrine.odm.mongodb.config.manager']);
	}
	
	/**
	 * Adds the "auto mapping" meta driver configuration to given driverchain
	 * 
	 * Adds an annotation driver for each drupal module with documents in <module dir>/ModuleDir/Document
	 * Namespace of these documents is based on an underscore to CamelCase conversion of the module name. 
	 * 
	 * E.g. foo_bar/FooBar/Document/User.php should have a namespace of FooBar\Document
	 * 
	 * Drupal's cache api is used to cache the found document dirs + namespaces.
	 * 
	 * @param DriverChain $chain
	 */	
	static function addAutoMapping(DriverChain $chain)
	{
		$key 	= md5(__METHOD__);
		
		$cache 	= cache_get($key);
		if (false === $cache) {
			$locations = array();

			foreach (module_list() as $module => $v) {
				$namespace 	= implode('',array_map('ucfirst',explode('_',$module)));
				$dir 		= DRUPAL_ROOT.DIRECTORY_SEPARATOR.drupal_get_path('module', $module).DIRECTORY_SEPARATOR.$namespace.DIRECTORY_SEPARATOR.'Document';
				
				if (is_dir($dir)) {
					$locations[] = array(
						'dir'	=> $dir,
						'ns'	=> "{$namespace}\\Document",
					);
				}
			}
			
			cache_set($key, $locations);
		} else {
			$locations = $cache->data;
		}
		
		foreach ((array)$locations as $location) {
			$driver = new AnnotationDriver(
				new AnnotationReader(),
				$location['dir']
			);
			$chain->addDriver($driver, $location['ns']);	
		}
	}
	
	/**
	 * Adds the document managers definition services including a default set of services.
	 * 
	 * For each manager definition, found in $config[managers], the following services are added:
	 * - %doctrine.odm.mongodb.dm.<name>%
	 * - %doctrine.odm.mongodb.dm.<name>.event_manager%
	 * - %doctrine.odm.mongodb.dm.<name>.configuration%
	 * - %doctrine.odm.mongodb.dm.<name>.connection%
	 * 
	 * Also these are set to defaults (without <name>) based on $config[default] or the first found $config[managers] which result in: 
	 * - %doctrine.odm.mongodb.dm%
	 * - %doctrine.odm.mongodb.dm.event_manager%
	 * - %doctrine.odm.mongodb.dm.configuration%
	 * - %doctrine.odm.mongodb.dm.connection% 
	 * 
	 * @param 	Drimple 					$drimple
	 * @param 	array 						$config			document mangers configration 
	 * 
	 * @throws 	\InvalidArgumentException	in case of invalid / missing configuration in $config 
	 */
	protected function loadDocumentManagers(Drimple $drimple, array $config)
	{
		if (!isset($config['managers']) || empty($config['managers'])) {
			throw new \InvalidArgumentException('Define at least one manager in doctrine.odm.mongodb.config.manager[managers]');
		}
	
		$self 		= $this;
		$default 	= isset($config['config']) ? $config['config'] : array();
		
		/**
		 * Add the services per manager
		 */
		foreach ($config['managers'] as $name => $manager) {
			$drimple["doctrine.odm.mongodb.dm.{$name}.event_manager"] = $drimple->share(function($c) {			
				return new EventManager();
			});
			
			if (!isset($manager['config'])) {
				$manager['config'] = array();
			}
			
			$manager['config'] = $manager['config'] + $default;
						
			$drimple["doctrine.odm.mongodb.dm.{$name}.configuration"] = $drimple->share(function($c) use ($manager, $self) {			
				foreach (array(
					'proxy_dir',
					'hydrator_dir',
					'database',
				) as $var) {
					if (!isset($manager['config'][$var])) {
						throw new \InvalidArgumentException(sprintf('Define "%s" in doctrine.odm.mongodb.config.manager[config] or doctrine.odm.mongodb.config.manager[%s][config]', $var, $name));
					}
				}
				
				$config = new Configuration();
	
				$config->setProxyDir($manager['config']['proxy_dir']);
				$config->setProxyNamespace(
					isset($manager['config']['proxy_namespace'])
					? $manager['config']['proxy_namespace']
					: 'Proxies'
				);
	
				$config->setHydratorDir($manager['config']['hydrator_dir']);
				$config->setHydratorNamespace(
					isset($manager['config']['hydrator_namespace'])
					? $manager['config']['hydrator_namespace']
					: 'Hydrators'
				);
			
				$config->setDefaultDB($manager['config']['database']);
				
				if (isset($manager['config']['meta_cache_class'])) {
					$config->setMetadataCacheImpl(new $manager['config']['meta_cache_class']);
				}
				
				$chain = new DriverChain();
				
				if (isset($manager['auto_mapping']) && true === $manager['auto_mapping']) {
					$self::addAutoMapping($chain);
				}
				if (isset($manager['drivers'])) {
		            foreach($manager['drivers'] as $document) {
						$driver = new AnnotationDriver(
							new AnnotationReader(),
							$document['path']
						);
						$chain->addDriver($driver, $document['namespace']);
		            }
				}
				AnnotationDriver::registerAnnotationClasses();
				$config->setMetadataDriverImpl($chain);
				return $config;
			});
			
			$drimple["doctrine.odm.mongodb.dm.{$name}.connection"] = $drimple->share(function($c) use ($manager) {
				return 
					isset($manager['connection']) 
					? $c["doctrine.odm.mongodb.connection.{$manager['connection']}"]
					: $c['doctrine.odm.mongodb.connection'];
	           					
			});
			
			$drimple["doctrine.odm.mongodb.dm.{$name}"] = $drimple->share(function($c) use ($name){
				return DocumentManager::create(
					$c["doctrine.odm.mongodb.dm.{$name}.connection"],
					$c["doctrine.odm.mongodb.dm.{$name}.configuration"],
					$c["doctrine.odm.mongodb.dm.{$name}.event_manager"]
				);
			});
		}
		if (isset($config['default'])) {
			if (!isset($config['managers'][$config['default']])) {
				throw new \InvalidArgumentException(sprintf('"%s" set in doctrine.odm.mongodb.config.manager[default] isn\'t a defined manager', $config['default']));
			}
			
			$default = $config['default'];
		} else {
			reset($config['managers']);
			$default = key($config['managers']);
		}
		
		foreach (array(
			'',
			'.event_manager',
			'.configuration',
			'.connection',
		) as $type) {
			$drimple["doctrine.odm.mongodb.dm{$type}"] = $drimple->share(function($c) use ($default, $type) {
				return $c["doctrine.odm.mongodb.dm.{$default}{$type}"]; 					 
			});
		}		
	}
	
	/** 
	 * Adds the connections managers definition services including a default set of services.
	 * 
	 * For each connection definition, found in $config[connections], the following services are added:
	 * - %doctrine.odm.mongodb.connection.<name>%
	 * - %doctrine.odm.mongodb.connection.<name>.event_manager%
	 * 
	 * Also these are set to defaults (without <name>) based on $config[default] or the first found $config[connections] which result in: 
	 * - %doctrine.odm.mongodb.connection%
	 * - %doctrine.odm.mongodb.connection.event_manager%
	 * 
	 * @param 	Drimple 					$drimple
	 * @param 	array 						$config			 connection configration 
	 * 
	 * @throws 	\InvalidArgumentException	in case of invalid / missing configuration in $config
	 */
	protected function loadConnections(Drimple $drimple, array $config)
	{
		if (!isset($config['connections']) || empty($config['connections'])) {
			throw new \InvalidArgumentException('Define at least one connection in doctrine.odm.mongodb.config.connection[connections]');
		}
		
		foreach ($config['connections'] as $name => $connection) {
			$drimple["doctrine.odm.mongodb.connection.{$name}.event_manager"] = $drimple->share(function($c) {			
				return new EventManager();
			});
			
			$drimple["doctrine.odm.mongodb.connection.{$name}"] = $drimple->share(function($c) use ($name, $connection) {	
				return new Connection(
					isset($connection['server']) 	? $connection['server'] 	: null,
					isset($connection['options']) 	? $connection['options']	: array(),
					$c["doctrine.odm.mongodb.dm.{$name}.configuration"],
					$c["doctrine.odm.mongodb.connection.{$name}.event_manager"]					
				);			 
			});
		}
		
		if (isset($config['default'])) {
			if (!isset($config['connections'][$config['default']])) {
				throw new \InvalidArgumentException(sprintf('"%s" set in doctrine.odm.mongodb.config.connection[default] isn\'t a defined connection', $config['default']));
			}
			
			$default = $config['default'];
		} else {
			reset($config['connections']);
			$default = key($config['connections']);
		}
		
		foreach (array(
			'',
			'.event_manager',
		) as $type) {
			$drimple["doctrine.odm.mongodb.connection{$type}"] = $drimple->share(function($c) use ($default, $type) {
				return $c["doctrine.odm.mongodb.connection.{$default}{$type}"]; 					 
			});
		}		
	}
}