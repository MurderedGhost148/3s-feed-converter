<?php

class DomUtils {
    /**
     * Добавляет или обновляет узел с текстом или дочерними элементами.
     *
     * @param DOMDocument $doc
     * @param DOMNode $parent
     * @param string $tagName
     * @param string|null $value
     * @param DOMElement|null $childContent
     * @throws DOMException
     */
    public static function upsertNode(DOMDocument $doc, DOMNode $parent, string $tagName, ?string $value = null, ?DOMElement $childContent = null): void
    {
        $xpath = new DOMXPath($doc);
        $node = $xpath->query($tagName, $parent)->item(0);

        if (!$node) {
            $node = $doc->createElement($tagName);
            $parent->appendChild($node);
        }

        if ($value !== null) {
            $node->nodeValue = htmlspecialchars($value);
        } elseif ($childContent !== null) {
            while ($node->hasChildNodes()) {
                $node->removeChild($node->firstChild);
            }
            foreach ($childContent->childNodes as $child) {
                $node->appendChild($child->cloneNode(true));
            }
        }
    }
}