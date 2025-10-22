<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/mutator/mutator-registry.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/model/service.php";

$mutators = new MutatorRegistry();

// Циан
{
    $service = Service::CIAN->value;

    // 1️⃣ Замена <currency> → <Currency>
    $mutators->add(function(DOMDocument $doc) {
        $xpath = new DOMXPath($doc);

        foreach ($xpath->query('//BargainTerms/currency') as $node) {
            $newNode = $doc->createElement('Currency', $node->nodeValue);
            $node->parentNode->replaceChild($newNode, $node);
        }
    }, $service, [""]);

    // 2️⃣ Замена текста в Description
    $mutators->add(function(DOMDocument $doc) {
        $xpath = new DOMXPath($doc);
        $replacements = [
            'Лес как часть жизни' => '&lt;b&gt;Лес как часть жизни&lt;/b&gt;',
            'и благоустройстве' => '&lt;b&gt;и благоустройстве&lt;/b&gt;',
            'Двор – это место отдыха' => '&lt;b&gt;Двор – это место отдыха&lt;/b&gt;',
            'Главные особенности:' => '&lt;b&gt;Главные особенности:&lt;/b&gt;',
            'Пространство:' => '&lt;b&gt;Пространство:&lt;/b&gt;',
            'Природный ландшафт:' => '&lt;b&gt;Природный ландшафт:&lt;/b&gt;',
            'Игровые и спортивные зоны:' => '&lt;b&gt;Игровые и спортивные зоны:&lt;/b&gt;',
            'Экологичность и долговечность:' => '&lt;b&gt;Экологичность и долговечность:&lt;/b&gt;',
            'Безопасность и уединение:' => '&lt;b&gt;Безопасность и уединение:&lt;/b&gt;',
            'Сохранение времени:' => '&lt;b&gt;Сохранение времени&lt;/b&gt;',
            '●	' => '-	'
        ];

        foreach ($xpath->query('//Description') as $descNode) {
            $text = $descNode->textContent;
            foreach ($replacements as $search => $replace) {
                $text = preg_replace("/$search/u", $replace, $text);
            }
            // Перезаписываем контент как CDATA
            while ($descNode->firstChild) {
                $descNode->removeChild($descNode->firstChild);
            }
            $descNode->appendChild($doc->createCDATASection($text));
        }
    }, $service, [""]);

    // 3️⃣ Добавление MortgageAllowed = true
    $mutators->add(function(DOMDocument $doc) {
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//BargainTerms') as $node) {
            $mortgageNode = $doc->createElement('MortgageAllowed', 'true');
            $node->appendChild($mortgageNode);
        }
    }, $service, [House::NG->value]);

    // 4️⃣ Работа с Photos / LayoutPhoto
    $mutators->add(function(DOMDocument $doc) {
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//Photos') as $photosNode) {
            $photoNodes = iterator_to_array($xpath->query('.//PhotoSchema', $photosNode));
            if (empty($photoNodes)) {
                continue;
            }

            $presetImages = [];
            $otherImages = [];

            foreach ($photoNodes as $photoNode) {
                $fullUrlNode = $photoNode->getElementsByTagName('FullUrl')->item(0);
                if (!$fullUrlNode) continue;

                $url = $fullUrlNode->textContent;

                if (str_contains($url, '/uploads/preset/')) {
                    $presetImages[] = $photoNode;
                } elseif (!str_contains($url, '/uploads/layout/')) {
                    $otherImages[] = $photoNode;
                }
            }

            $layoutPhoto = null;

            if (!empty($presetImages)) {
                $lastPreset = array_pop($presetImages);
                if (count($presetImages) >= 1) {
                    $secondLast = array_pop($presetImages);
                    $url = $secondLast->getElementsByTagName('FullUrl')->item(0)?->textContent;
                    if ($url && str_ends_with($url, '.jpg')) {
                        array_unshift($otherImages, $lastPreset);
                        $lastPreset = $secondLast;
                    }
                }
                $layoutPhoto = $lastPreset;
            } else {
                $layoutPhoto = end($otherImages);
            }

            if ($layoutPhoto) {
                $layoutPhotoClone = $layoutPhoto->cloneNode(true);

                foreach ($xpath->query('../LayoutPhoto', $photosNode->parentNode) as $old) {
                    $old->parentNode->removeChild($old);
                }

                $layoutNode = $doc->createElement('LayoutPhoto');
                foreach ($layoutPhotoClone->childNodes as $child) {
                    $layoutNode->appendChild($child->cloneNode(true));
                }
                $photosNode->parentNode->appendChild($layoutNode);
            }

            while ($photosNode->hasChildNodes()) {
                $photosNode->removeChild($photosNode->firstChild);
            }
            foreach ($otherImages as $img) {
                $photosNode->appendChild($img->cloneNode(true));
            }
        }
    }, $service, [""]);
}