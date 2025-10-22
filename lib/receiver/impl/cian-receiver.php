<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/receiver/receiver.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/model/service.php";

use Receiving\Context;

class CianReceiver extends Receiver
{
    protected function getService() : Service
    {
        return Service::CIAN;
    }

    /**
     * @throws Exception
     */
    protected function processXml(XMLReader $xml, Context $context) : void
    {
        $feed_version = 2;

        while ($xml->read()) {
            if(XMLReader::ELEMENT == $xml->nodeType) {
                $name = $xml->name;

                if($name == 'feed_version' || $name == 'object') {
                    $node = new SimpleXMLElement($xml->readOuterXML());

                    if($name == 'feed_version') {
                        $feed_version = $this->processFeedVersion($node, $context);
                    } else {
                        $this->clearOnce($context);

                        try {
                            $this->processItem($node, $context);
                        } catch (DatabaseException|ReceiveException $ex) {
                            Logger::warn($ex->getMessage());
                        }
                    }
                }
            }
        }

        $this->insertTask($context, ['feed_version' => $feed_version]);
    }

    /**
     * @throws DatabaseException|ReceiveException
     */
    private function processItem(SimpleXMLElement $node, Context $context): void
    {
        $house = $context->getHouse();

        $externalId = $this->processExternalId($node);
        $type = $this->processType($node);

        $this->saveItem($externalId, $house, $type, $node);
    }

    /**
     * @throws ReceiveException
     */
    private function processFeedVersion(SimpleXMLElement $node, Context $context): string
    {
        $feed_version = (string) $node;
        $house = $context->getHouse();
        $url = $context->getUrl();

        if(!isset($feed_version) || !is_numeric($feed_version)){
            throw new ReceiveException(
                "Поле feed_version пустое или не содержит число для дома $house: $url"
            );
        }

        return $feed_version;
    }

    /**
     * @throws ReceiveException
     */
    private function processExternalId(SimpleXMLElement $node): string
    {
        $externalId = (string) $node->ExternalId;
        unset($node->ExternalId);

        if(empty($externalId) || !is_numeric($externalId)) {
            throw new ReceiveException(
                "Поле ExternalId не указано или не является числом: " . print_r((array) $node, true)
            );
        }

        return $externalId;
    }

    /**
     * @throws ReceiveException
     */
    private function processType(SimpleXMLElement $node): string
    {
        $category = (string) $node->Category;
        $type = null;

        if(!empty($category)) {
            switch($category){
                case 'flatSale':
                case 'newBuildingFlatSale': {
                    $type = 'newBuildingFlatSale';

                    break;
                }
                case 'newBuildingTownhouseSale':
                case 'townhouseSale': {
                    $type = 'townhouseSale';

                    break;
                }
                default: {
                    throw new ReceiveException(
                        "Поле Category имеет недопустимое значение: $category"
                    );
                }
            }
        }

        if(empty($type)) {
            throw new ReceiveException("Не удалось определить тип объекта");
        }

        return $type;
    }
}