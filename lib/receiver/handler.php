<?php
require_once __DIR__ . "/../../config/app-config.php";
require_once __DIR__ . "/../../lib/receiver/impl/cian-receiver.php";

/**
 * @var array $config
 */

set_time_limit(0);

// ЦИАН
{
    $receiver = new CianReceiver();
    $receiver->run($config['sources']['cian'] ?? []);
}