<?php
require_once __DIR__ . "/../../config/app-config.php";
require_once __DIR__ . "/../../lib/utils/base.php";
require_once __DIR__ . "/../../lib/processor/context.php";
require_once __DIR__ . "/../../model/service.php";

/**
 * @var array $config
 * @var DbUtils $dbUtils
 */

use Processing\Context;

abstract class Processor {
    protected abstract function getService() : Service;
    protected abstract function preProcess(Context $context, $command);
    protected abstract function postProcess(Context $context, $command);
    /**
     * @throws Exception
     */
    protected abstract function processItem(Context $context, $externalId, $type, $xml);

    public function run(array $task) : void
    {
        $command = json_decode($task['command'], true);
        $context = $this->buildContext($task);

        try {
            $this->process($context, $command);
        } catch (Exception $ex) {
            Logger::error("В процессе обработки данных возникла ошибка: {$ex->getMessage()}");
        }

        $this->afterProcess($task);
    }

    /**
     * @throws Exception
     */
    protected function process(Context $context, $command) : void
    {
        global $dbUtils;

        $service = $this->getService()->value;
        $house = $context->getHouse();
        $handledIds = array();

        $this->preProcess($context, $command);

        {
            $count = $dbUtils->getRowsCount('profitbase_data', ["service = '$service'", "house = '$house'"]);
            if($count > 0){
                do {
                    $query = array();
                    $query[] = "service = '$service'";
                    $query[] = "house = '$house'";

                    if (count($handledIds) > 0) {
                        $query[] = "id NOT IN (" . implode(",", $handledIds) . ")";
                    }

                    $data = $dbUtils->getProfitBaseElements($query, ['limit' => 1])[0] ?? [];

                    if (!empty($data)) {
                        $type = $data['category'];
                        $externalId = $data['id'];
                        $xml = $data['xml_data'];
                        unset($data);

                        $this->processItem($context, $externalId, $type, $xml);

                        $handledIds[] = $externalId;
                    } else {
                        break;
                    }
                } while(true);
            }
        }

        $this->postProcess($context, $command);
    }

    protected final function write(string $str, Context $context) : void
    {
        $context->getWriter()->write($str);
    }

    private function buildContext(array $task) : Context
    {
        $FILE_DIR = __DIR__ . "/../../files/{$task['house']}";
        $FILE_NAME = "{$task['service']}.xml";

        return new Context($task['house'], new Writer($FILE_DIR, $FILE_NAME));
    }

    private function afterProcess(array $task) : void
    {
        global $dbUtils;

        $dbUtils->deleteTask($task['id']);
        $dbUtils->clearTable(
            'profitbase_data', ["house = '{$task['house']}'", "service = '{$task['service']}'"]
        );
    }
}