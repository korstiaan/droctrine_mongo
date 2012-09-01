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

use Droctrine\Mongo\Cache\DrupalCache;

use Doctrine\Tests\Common\Cache\CacheTest as BaseTest;

class CacheTest extends BaseTest
{
    protected function _getCacheDriver()
    {
        return new DrupalCache();
    }

    public function testGetStats()
    {
        $cache = $this->_getCacheDriver();
        $stats = $cache->getStats();

        $this->assertNull($stats);
    }

    public function testLifetime()
    {
        $cache = $this->_getCacheDriver();
        $cache->save('lifetimed_key',  'foo', 1);
        $cache->save('lifetimed_key2', 'foo', 30);
        sleep(3);
        $this->assertFalse($cache->contains('lifetimed_key'));
        $this->assertTrue($cache->contains('lifetimed_key2'));
    }
}
