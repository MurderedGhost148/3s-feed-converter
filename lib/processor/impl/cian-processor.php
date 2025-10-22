<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/processor/processor.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/xml-serializer.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/model/service.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/config/mutator-config.php";

use Processing\Context;

/**
 * @var MutatorRegistry $mutators
 */

class CianProcessor extends Processor {
    protected function getService() : Service
    {
        return Service::CIAN;
    }

    protected function preProcess(Context $context, $command)
    {
        $this->write(
            "<?xml version='1.0' encoding='UTF-8'?>\n<feed><feed_version>{$command['feed_version']}</feed_version>", $context
        );
    }

    protected function postProcess(Context $context, $command)
    {
        $this->write("\n</feed>", $context);
    }


    protected function processItem(Context $context, $externalId, $type, $xml) : void
    {
        global $mutators;

        $object = json_decode(
            json_encode(simplexml_load_string($xml), JSON_UNESCAPED_UNICODE), false
        );
        $object->ExternalId = $externalId;
        $object->Category = $type;

        $mutators->apply($object, $this->getService()->value, $context->getHouse());

        $xml_string = XMLSerializer::Serialize($object, 'object');
        $object = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_NOXMLDECL);
        unset($xml_string);

        $this->write(
            str_replace(
                '<?xml version="1.0" encoding="UTF-8"?>', '', trim($object->asXML(), "\n")
            ), $context
        );
    }
}