<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/processor/processor.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/utils/dom.php";
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

    /**
     * @throws DOMException
     */
    protected function processItem(Context $context, $externalId, $type, $xml) : void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        $objectNode = $dom->documentElement;

        DomUtils::upsertNode($dom, $objectNode, 'ExternalId', $externalId);
        DomUtils::upsertNode($dom, $objectNode, 'Category', $type);

        global $mutators;
        $mutators->apply($dom, $this->getService()->value, $context->getHouse());

        $this->write($dom->saveXML($dom->documentElement), $context);
    }
}