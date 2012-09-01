<?php

/**
 * This file is part of Droctrine Mongo
 *
 * (c) Korstiaan de Ridder <korstiaan@korstiaan.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Droctrine\Mongo\Cache;

use Doctrine\Common\Cache\CacheProvider;

class DrupalCache extends CacheProvider
{
    protected $bin;

    public function __construct($bin = 'cache')
    {
        $this->bin = $bin;
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\Cache.CacheProvider::doFetch()
     */
    protected function doFetch($id)
    {
        $data = cache_get($id, $this->bin);

        if (false === $data || !$data instanceof \stdClass) {
            return false;
        }

        if (0 <= $data->expire && $data->expire <= $this->getTimeStamp()) {
            return false;
        }

        return $data->data;
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\Cache.CacheProvider::doContains()
     */
    protected function doContains($id)
    {
        return false !== $this->doFetch($id);
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\Cache.CacheProvider::doSave()
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $lifeTime = 0 >= (int) $lifeTime ? CACHE_TEMPORARY : $lifeTime += $this->getTimeStamp();

        return cache_set($id, $data, $this->bin, $lifeTime);
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\Cache.CacheProvider::doFlush()
     */
    protected function doFlush()
    {
        $namespace = $this->getNamespace();
        if (!empty($namespace)) {
            cache_clear_all($namespace, $this->bin, true);
        } else {
            cache_clear_all(null, $this->bin, false);
        }
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\Cache.CacheProvider::doDelete()
     */
    protected function doDelete($id)
    {
        cache_clear_all($id, $this->bin);
    }

    /**
     * (non-PHPdoc)
     * @see Doctrine\Common\Cache.CacheProvider::doGetStats()
     */
    protected function doGetStats()
    {
        return null;
    }

    /**
     * Returns current timestamp
     *
     * @return int
     */
    protected function getTimeStamp()
    {
        return time();
    }
}
