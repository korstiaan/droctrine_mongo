#!/bin/sh
if [ "${MEMCACHED_VERSION:+1}" = 1 ]; then
    wget http://pecl.php.net/get/memcached-${MEMCACHED_VERSION}.tgz
    tar -xzf memcached-${MEMCACHED_VERSION}.tgz
    sh -c "cd memcached-${MEMCACHED_VERSION} && phpize && ./configure && make && sudo make install"
    echo "extension=memcached.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
fi

if [ "${APC_VERSION:+1}" = 1 ]; then
    wget http://pecl.php.net/get/APC-${APC_VERSION}.tgz
    tar -xzf APC-${APC_VERSION}.tgz
    sh -c "cd APC-${APC_VERSION} && phpize && ./configure && make && sudo make install"
    echo "extension=apc.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
fi

if [ "${MEMCACHE_VERSION:+1}" = 1 ]; then
    wget http://pecl.php.net/get/memcache-${MEMCACHE_VERSION}.tgz
    tar -xzf memcache-${MEMCACHE_VERSION}.tgz
    sh -c "cd memcache-${MEMCACHE_VERSION} && phpize && ./configure && make && sudo make install"
    echo "extension=memcache.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
fi

if [ "${MONGO_VERSION:+1}" = 1 ]; then
    wget http://pecl.php.net/get/mongo-${MONGO_VERSION}.tgz
    tar -xzf mongo-${MONGO_VERSION}.tgz
    sh -c "cd mongo-${MONGO_VERSION} && phpize && ./configure && make && sudo make install"
    echo "extension=mongo.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`
fi
