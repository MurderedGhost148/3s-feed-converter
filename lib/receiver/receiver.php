<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/config/app-config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/utils/base.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/receiver/context.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/model/exception/receiving-exception.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/model/exception/database-exception.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/model/service.php";

/**
 * @var array $config
 * @var DbUtils $dbUtils
 */

use Receiving\Context;

abstract class Receiver {
    protected abstract function getService() : Service;
    protected abstract function processXml(XMLReader $xml, Context $context) : void;

    public function run(array $houses) : void
    {
        foreach ($houses as $house => $url){
            try{
                $this->process(new Context($house, $url));
                usleep(200);
            }
            catch (Exception $ex)
            {
                Logger::error(
                    "Возникла ошибка при чтении xml по адресу $url: {$ex->getMessage()}"
                );

                continue;
            }
        }
    }

    /**
     * @throws DatabaseException
     */
    protected final function clearOnce(Context $context): void
    {
        if (!$context->isCleared()) {
            $this->clearData($context);
            $context->setCleared(true);
        }
    }

    /**
     * @throws DatabaseException
     */
    protected final function saveItem($externalId, $house, $type, $node) : void
    {
        global $dbUtils;

        if(!$dbUtils->addProfitBaseElement($externalId, $this->getService()->value, $house, $type, $node->asXML())){
            throw new DatabaseException("Не удалось добавить элемент: $externalId");
        }
    }

    protected final function insertTask(Context $context, array $command) : void
    {
        global $dbUtils;

        $house = $context->getHouse();

        $dbUtils->insertTask($this->getService()->value, $house, $command);
    }

    /**
     * @throws Exception
     */
    private function process(Context $context) : void
    {
        $reader = new XMLReader();
        $reader->open($context->getUrl());
        $this->processXml($reader, $context);
        $reader->close();
    }

    /**
     * @throws DatabaseException
     */
    private function clearData(Context $context) : void
    {
        global $dbUtils;

        $service = $this->getService()->value;
        $house = $context->getHouse();
        $url = $context->getUrl();

        $result = $dbUtils->clearTable(
            'profitbase_data', ["service = '$service'", "house = '$house'"]
        );

        if(!$result){
            throw new DatabaseException(
                "Устаревшие данные не были удалены из базы данных корректно для дома $house: $url"
            );
        }
    }
}