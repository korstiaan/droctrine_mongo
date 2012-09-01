<?php

/**
 * This file is part of Droctrine Mongo
 *
 * (c) Korstiaan de Ridder <korstiaan@korstiaan.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Drunit\Drunit;

$autoload = require __DIR__.'/../vendor/autoload.php';

$autoload->add('Doctrine\\Tests', __DIR__.'/../vendor/doctrine/common/tests');

if (!class_exists('Drunit\\Drunit')) {
    throw new \RuntimeException('Drunit not found, make sure you have installed all dependencies (--dev)');
}

Drunit::bootstrap();
Drunit::enableModule(__DIR__.'/fixtures',   array('mongotest', 'mongo_test', 'mongo_init'));
Drunit::enableModule(__DIR__.'/../modules', array('drimple', 'nsautoload'));
