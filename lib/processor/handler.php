<?php
require_once __DIR__ . "/../../config/app-config.php";
require_once __DIR__ . "/../../lib/utils/base.php";
require_once __DIR__ . "/../../lib/processor/impl/cian-processor.php";

/**
 * @var array $config
 * @var DbUtils $dbUtils
 */

set_time_limit(0);

do {
    $task = $dbUtils->getOneTask();

    if (!empty($task)) {
        try {
            $processor = null;
            switch ($task['service']) {
                case 'cian':
                {
                    $processor = new CianProcessor();

                    break;
                }
                default: {
                    throw new ProcessException("Необрабатываемый сервис: {$task['service']}");
                }
            }

            $processor->run($task);
            Logger::info("Задача #{$task['id']} выполнена");
        } catch (ProcessException $ex) {
            Logger::error($ex->getMessage());
        }
    }
} while (!empty($task));