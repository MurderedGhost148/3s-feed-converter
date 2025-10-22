<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/lib/mutator/mutator-registry.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/model/service.php";

$mutators = new MutatorRegistry();

// Циан
{
    $service = Service::CIAN->value;

    $mutators->add(function(object $object){
        if(isset($object->BargainTerms->currency)){
            $object->BargainTerms->Currency = $object->BargainTerms->currency;

            unset($object->BargainTerms->currency);
        }
    }, $service, [""]);

    $mutators->add(function(object $object){
        if((is_object($object->Description) && !empty(get_object_vars($object->Description))) || (!is_object($object->Description) && !empty($object->Description))){
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
                'Сохранение времени:' => '&lt;b&gt;Сохранение времени:&lt;/b&gt;',
                '●	' => '-	'
            ];

            foreach ($replacements as $search => $replace) {
                $object->Description = preg_replace("/$search/", $replace, $object->Description);
            }
        }
    }, $service, [""]);

    $mutators->add(function(object $object){
        $object->BargainTerms->MortgageAllowed = true;
    }, $service, [House::NG->value]);

    $mutators->add(function(object $object){
        if (isset($object->Photos)) {
            if (isset($object->Photos->PhotoSchema)) {
                if (is_array($object->Photos->PhotoSchema)) {
                    $presetImages = [];
                    $otherImages = [];

                    foreach ($object->Photos->PhotoSchema as $schema) {
                        if(isset($schema->FullUrl)) {
                            if(str_contains($schema->FullUrl, '/uploads/preset/')) {
                                $presetImages[] = $schema;
                            } else {
                                if(!str_contains($schema->FullUrl, '/uploads/layout/')) {
                                    $otherImages[] = $schema;
                                }
                            }
                        }
                    }

                    // Если есть хотя бы одна фотография с папкой "preset"
                    if (!empty($presetImages)) {
                        $lastPresetImage = array_pop($presetImages); // Последняя фотография среди всех фотографий с папкой "preset"

                        // Если фотографий с папкой "preset" больше одной
                        $count = ($presetImages);
                        if ($count >= 1) {
                            $secondLastPresetImage = array_pop($presetImages); // Предпоследняя фотография с папкой "preset"

                            if(str_ends_with($secondLastPresetImage->FullUrl, '.jpg')){
                                array_unshift($otherImages, $lastPresetImage); // Добавляем предпоследнюю фотографию с папкой "preset" в начало массива
                                $lastPresetImage = clone $secondLastPresetImage;
                            }
                        }

                        $object->LayoutPhoto = $lastPresetImage;
                    } else {
                        // Если нет фотографий с папкой "preset", выбираем последнюю фотографию среди всех фото и устанавливаем её в LayoutPhoto
                        $lastPhoto = end($otherImages);
                        $object->LayoutPhoto = clone $lastPhoto;
                    }

                    // Обновляем свойство Photos
                    $object->Photos->PhotoSchema = $otherImages;
                } else {
                    // Если PhotoSchema не является массивом, проверяем и используем единственную фотографию, если она является планировкой
                    if (isset($object->Photos->PhotoSchema->FullUrl) && str_contains($object->Photos->PhotoSchema->FullUrl, '/uploads/preset/')) {
                        $object->LayoutPhoto = clone $object->Photos->PhotoSchema;
                    }
                }
            }
        }
    }, $service, [""]);
}