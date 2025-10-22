<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/config/app-config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/receiver/impl/cian-receiver.php";

/**
 * @var array $config
 */

set_time_limit(0);

// ЦИАН
{
    $receiver = new CianReceiver();
    $receiver->run($config['sources']['cian'] ?? []);
}