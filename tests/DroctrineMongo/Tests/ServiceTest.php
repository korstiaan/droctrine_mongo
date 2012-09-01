<?php

/**
 * This file is part of Droctrine Mongo
 *
 * (c) Korstiaan de Ridder <korstiaan@korstiaan.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Droctrine\Mongo\Tests;

use Droctrine\Mongo\Provider\DoctrineMongoDBProvider;

use Drimple\Drimple;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testServices()
    {
        $drimple = new Drimple();

        $drimple->register(new DoctrineMongoDBProvider(), $this->getMinimumConfig());

        foreach (array ('foo','bar') as $name) {
            $this->assertInstanceOf('Doctrine\\ODM\\MongoDB\\DocumentManager', $drimple["doctrine.odm.mongodb.dm.{$name}"]);
            $this->assertInstanceOf('Doctrine\\Common\\EventManager',          $drimple["doctrine.odm.mongodb.dm.{$name}.event_manager"]);
            $this->assertInstanceOf('Doctrine\\ODM\\MongoDB\\Configuration',   $drimple["doctrine.odm.mongodb.dm.{$name}.configuration"]);
            $this->assertInstanceOf('Doctrine\\MongoDB\\Connection',           $drimple["doctrine.odm.mongodb.dm.{$name}.connection"]);

            $this->assertInstanceOf('Doctrine\\MongoDB\\Connection',           $drimple["doctrine.odm.mongodb.connection.{$name}"]);
            $this->assertInstanceOf('Doctrine\\Common\\EventManager',          $drimple["doctrine.odm.mongodb.connection.{$name}.event_manager"]);
            $this->assertInstanceOf('Doctrine\\ODM\\MongoDB\\Configuration',   $drimple["doctrine.odm.mongodb.connection.{$name}.configuration"]);
        }

        $this->assertNotSame($drimple['doctrine.odm.mongodb.dm.foo'], $drimple['doctrine.odm.mongodb.dm.bar']);
        $this->assertNotSame($drimple['doctrine.odm.mongodb.dm.foo.event_manager'], $drimple['doctrine.odm.mongodb.dm.bar.event_manager']);
        $this->assertNotSame($drimple['doctrine.odm.mongodb.dm.foo.configuration'], $drimple['doctrine.odm.mongodb.dm.bar.configuration']);
        $this->assertNotSame($drimple['doctrine.odm.mongodb.dm.foo.connection'], $drimple['doctrine.odm.mongodb.dm.bar.connection']);
        $this->assertNotSame($drimple['doctrine.odm.mongodb.connection.foo'], $drimple['doctrine.odm.mongodb.connection.bar']);
        $this->assertNotSame($drimple['doctrine.odm.mongodb.connection.foo.event_manager'], $drimple['doctrine.odm.mongodb.connection.bar.event_manager']);
        $this->assertNotSame($drimple['doctrine.odm.mongodb.connection.foo.configuration'], $drimple['doctrine.odm.mongodb.connection.bar.configuration']);
    }

    public function testDefaultDefaults()
    {
        $drimple = new Drimple();
        $drimple->register(new DoctrineMongoDBProvider(), $this->getMinimumConfig());

        $this->assertSame($drimple['doctrine.odm.mongodb.dm.bar'],               $drimple['doctrine.odm.mongodb.dm']);
        $this->assertSame($drimple['doctrine.odm.mongodb.dm.bar.event_manager'], $drimple['doctrine.odm.mongodb.dm.event_manager']);
        $this->assertSame($drimple['doctrine.odm.mongodb.dm.bar.configuration'], $drimple['doctrine.odm.mongodb.dm.configuration']);
        $this->assertSame($drimple['doctrine.odm.mongodb.dm.bar.connection'],    $drimple['doctrine.odm.mongodb.dm.connection']);

        $this->assertSame($drimple['doctrine.odm.mongodb.connection.foo'],               $drimple['doctrine.odm.mongodb.connection']);
        $this->assertSame($drimple['doctrine.odm.mongodb.connection.foo.event_manager'], $drimple['doctrine.odm.mongodb.connection.event_manager']);
        $this->assertSame($drimple['doctrine.odm.mongodb.connection.foo.configuration'], $drimple['doctrine.odm.mongodb.connection.configuration']);

        $this->assertSame($drimple['doctrine.odm.mongodb.dm.bar.connection'], $drimple['doctrine.odm.mongodb.dm.crux.connection']);
    }

    /**
     * @dataProvider getDefaultTests
     */
    public function testConfigDefaults($drimple, $dmDefault, $conDefault)
    {

        $this->assertSame($drimple["doctrine.odm.mongodb.dm.{$dmDefault}"],               $drimple['doctrine.odm.mongodb.dm']);
        $this->assertSame($drimple["doctrine.odm.mongodb.dm.{$dmDefault}.event_manager"], $drimple['doctrine.odm.mongodb.dm.event_manager']);
        $this->assertSame($drimple["doctrine.odm.mongodb.dm.{$dmDefault}.configuration"], $drimple['doctrine.odm.mongodb.dm.configuration']);
        $this->assertSame($drimple["doctrine.odm.mongodb.dm.{$dmDefault}.connection"],    $drimple['doctrine.odm.mongodb.dm.connection']);

        $this->assertSame($drimple["doctrine.odm.mongodb.connection.{$conDefault}"],               $drimple['doctrine.odm.mongodb.connection']);
        $this->assertSame($drimple["doctrine.odm.mongodb.connection.{$conDefault}.event_manager"], $drimple['doctrine.odm.mongodb.connection.event_manager']);
        $this->assertSame($drimple["doctrine.odm.mongodb.connection.{$conDefault}.configuration"], $drimple['doctrine.odm.mongodb.connection.configuration']);
    }

    public function getDefaultTests()
    {
        $ret     = array();

        $drimple = new Drimple();
        $drimple->register(new DoctrineMongoDBProvider(), $this->getMinimumConfig());

        $ret[] = array($drimple, 'bar', 'foo');

        $drimple = new Drimple();

        $config  = $this->getMinimumConfig();
        $config['doctrine.odm.mongodb.config.connection']['default'] = 'bar';
        $config['doctrine.odm.mongodb.config.manager']['default']    = 'foo';

        $drimple->register(new DoctrineMongoDBProvider(), $config);

        $ret[] = array($drimple, 'foo', 'bar');

        return $ret;
    }

    /**
     * @dataProvider getCachingConfigs
     */
    public function testMappingIsCached($config)
    {
        $cache = $this->getMock('Droctrine\Mongo\\Cache\\DrupalCache');

        $cache->expects($this->at(0))
             ->method('fetch')
             ->will($this->returnValue(false));

        $cache->expects($this->at(2))
             ->method('fetch')
             ->will($this->returnValue(array()));

        $cache->expects($this->once())
             ->method('save');

        for ($i = 0; $i < 2; $i++) {
            $drimple = new Drimple();

            $drimple->register(new DoctrineMongoDBProvider(), $config);
            $drimple['doctrine.odm.mongodb.cache'] = $cache;

            $helper = $this->getMock('Droctrine\Mongo\\Helper\\DrupalHelper');
            $helper->expects($this->any())
                 ->method('getModuleList')
                 ->will($this->returnValue(array()));

            $drimple['doctrine.odm.mongodb.helper'] = $helper;

            $dm = $drimple['doctrine.odm.mongodb.dm'];
        }
    }

    public function getCachingConfigs()
    {
        $config = $this->getMinimumConfig();
        foreach ($config['doctrine.odm.mongodb.config.manager']['managers'] as $k => $manager) {
            unset($config['doctrine.odm.mongodb.config.manager']['managers'][$k]['auto_mapping']);
        }

        return array(
            array($config),
        );
    }

    public function testManagerConnection()
    {
        $drimple = new Drimple();
        $drimple->register(new DoctrineMongoDBProvider(), $this->getMinimumConfig());

        $this->assertSame($drimple['doctrine.odm.mongodb.dm.bar.connection'],  $drimple['doctrine.odm.mongodb.connection.foo']);
        $this->assertSame($drimple['doctrine.odm.mongodb.dm.foo.connection'],  $drimple['doctrine.odm.mongodb.connection.bar']);
        $this->assertSame($drimple['doctrine.odm.mongodb.dm.crux.connection'], $drimple['doctrine.odm.mongodb.connection.foo']);
    }

    protected function getMinimumConfig()
    {
        return array(
            'doctrine.odm.mongodb.config.connection' => array(
                'connections' => array(
                    'foo' => array(
                    ),
                    'bar' => array(
                    ),
                ),
            ),
            'doctrine.odm.mongodb.config.manager' => array(
                'managers'    => array(
                    'bar'    => array(
                        'connection' => 'foo',
                        'auto_mapping' => false,

                    ),
                    'foo'    => array(
                        'connection' => 'bar',
                        'auto_mapping' => false,

                    ),
                    'crux'    => array(
                        'auto_mapping' => false,

                    ),
                ),
                'config'    => array(
                    'database'	   => 'db_mongo',
                ),
            ),
        );
    }

    protected function addDrupalMocks2(Drimple $drimple)
    {
        return;
        $cache = $this->getMock('Droctrine\Mongo\\Cache\\DrupalCache');

        $cache->expects($this->any())
             ->method('get')
             ->will($this->returnValue(false));

        $drimple['doctrine.odm.mongodb.cache'] = $cache;

        $helper = $this->getMock('Droctrine\Mongo\\Helper\\DrupalHelper');
        $helper->expects($this->any())
             ->method('getModuleList')
             ->will($this->returnValue(array()));

        $drimple['doctrine.odm.mongodb.helper'] = $helper;
    }

}
