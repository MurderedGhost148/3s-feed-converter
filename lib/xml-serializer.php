<?php

class XMLSerializer
{
    private static function GetClassNameWithoutNamespace($object)
    {
        $class_name = get_class($object);
        $arr = explode('\\', $class_name);
        return end($arr);
    }

    public static function Serialize($object, $root = 'Ad')
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';
        $xml .= "<$root";
        $xml .= self::SerializeAttributes($object);
        $xml .= '>';
        $xml .= self::SerializeNode($object);
        $xml .= "</$root>";
        return $xml;
    }

    private static function SerializeAttributes(&$data)
    {
        $attributes = '';
        if (is_array($data) && isset($data['@attributes'])) {
            foreach ($data['@attributes'] as $attr_key => $attr_value) {
                $attributes .= ' ' . $attr_key . '="' . htmlspecialchars($attr_value, ENT_QUOTES) . '"';
            }
            unset($data['@attributes']);
        } elseif (is_object($data) && property_exists($data, '@attributes')) {
            foreach ($data->{'@attributes'} as $attr_key => $attr_value) {
                $attributes .= ' ' . $attr_key . '="' . htmlspecialchars($attr_value, ENT_QUOTES) . '"';
            }
            unset($data->{'@attributes'});
        }
        return $attributes;
    }

    private static function SerializeNode($node, $parent_node_name = false, $is_array_item = false)
    {
        $xml = '';
        if (is_object($node)) {
            $vars = get_object_vars($node);
        } else if (is_array($node)) {
            $vars = $node;
        } else {
            throw new Exception('Coś poszło nie tak');
        }

        foreach ($vars as $k => $v) {
            if (is_object($v)) {
                $node_name = ($parent_node_name ? $parent_node_name : $k);
                if (!$is_array_item) {
                    $node_name = $k;
                }

                $xml .= '<' . $node_name;
                $xml .= self::SerializeAttributes($v);
                $addition = self::SerializeNode($v);
                if ($addition == '') {
                    $xml .= '/>';
                } else {
                    $xml .= '>';
                    $xml .= $addition;
                    $xml .= '</' . $node_name . '>';
                }
            } else if (is_array($v)) {
                if (count($v) > 0) {
                    if (is_object(reset($v))) {
                        $addition = self::SerializeNode($v, $k, true);
                    } else {
                        $addition = self::SerializeNode($v, gettype(reset($v)), true);
                    }
                } else {
                    $addition = self::SerializeNode($v, false, true);
                }
                $xml .= $addition;
            } else {
                $node_name = ($parent_node_name ? $parent_node_name : $k);
                if ($v === null) {
                    continue;
                } else {
                    $xml .= '<' . $node_name . '>';
                    if (is_bool($v)) {
                        $xml .= $v ? 'true' : 'false';
                    } else {
                        $xml .= preg_replace_callback('/<!\[CDATA\[(.*?)\]\]>/s', function ($matches) {
                            return '<![CDATA[' . html_entity_decode($matches[1], ENT_QUOTES) . ']]>';
                        }, $v);
                    }
                    $xml .= '</' . $node_name . '>';
                }
            }
        }
        return $xml;
    }
}