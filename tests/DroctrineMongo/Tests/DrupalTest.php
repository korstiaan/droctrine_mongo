<?php

/**
 * This file is part of Droctrine Mongo
 *
 * (c) Korstiaan de Ridder <korstiaan@korstiaan.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DroctrineMongo\Tests;

class DrupalTest extends \PHPUnit_Framework_TestCase
{
    public function testDocumentsFoundAndMapped()
    {
        $dm   = drimple()->get('doctrine.odm.mongodb.dm');
        $meta = $dm->getClassMetadata('Mongotest\\Document\\Foo');

        $this->assertEquals(array('id', 'name'), $meta->getFieldNames());

        $meta = $dm->getClassMetadata('MongoTest\\Document\\Bar');
        $this->assertEquals(array('id', 'content'), $meta->getFieldNames());
    }
}
