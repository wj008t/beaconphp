<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 14:06
 */

namespace widget;


use beacon\Field;
use beacon\Utils;

class RadioGroup extends Hidden
{
    public function code(Field $field, $args)
    {

        $id = isset($args['id']) ? $args['id'] : $field->boxId;
        $name = isset($args['name']) ? $args['name'] : $field->boxName;
        $class = isset($args['class']) ? $args['class'] : $field->boxClass;
        $style = isset($args['style']) ? $args['style'] : $field->boxStyle;
        $options = isset($args['@options']) ? $args['@options'] : $field->options;
        $options = $options == null ? [] : $options;
        $value = isset($args['value']) ? $args['value'] : $field->value;
        if ($value === null) {
            $value = $field->default;
        }

        $args['value'] = '';
        $args['style'] = '';
        $args['class'] = '';
        $args['name'] = '';
        $args['type'] = '';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);

        $out = [];
        if ($field->useUlList) {
            $out[] = '<ul id="radio-group-' . $id . '"';
            if ($class !== null) {
                $out[] = ' class="' . $class . '"';
            }
            if ($style !== null) {
                $out[] = ' style="' . $style . '"';
            }
            $out[] = '>' . "\n";
            $keys = array_keys($options);
            $endKey = end($keys);
            foreach ($options as $key => $item) {
                if ($item == null) {
                    continue;
                }
                if (!is_array($item)) {
                    $item = ['value' => $item];
                }
                $text = isset($item['text']) ? $item['text'] : null;
                $tips = isset($item['tips']) ? $item['tips'] : null;
                $val = isset($item['value']) ? $item['value'] : null;
                if ($val === null) {
                    $val = $text;
                }
                $selected = strval($val) == strval($value) ? ' checked="checked"' : '';
                $out[] = '<li><label>';
                if ($endKey === $key) {
                    $selected .= ' ' . join(' ', $attr);
                }
                $out[] = '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($val) . '"' . $selected . '/>';
                $out[] = '<span>' . htmlspecialchars($text);
                if (!empty(strval($tips))) {
                    $out[] = '<em>' . htmlspecialchars($tips) . '</em>';
                }
                $out[] = '</span></label></li>' . "\n";
            }
            $out[] = '</ul>' . "\n";
        } else {
            $keys = array_keys($options);
            $endKey = end($keys);
            foreach ($options as $key => $item) {
                if ($item == null) {
                    continue;
                }
                if (!is_array($item)) {
                    $item = ['value' => $item];
                }
                $text = isset($item['text']) ? $item['text'] : null;
                $tips = isset($item['tips']) ? $item['tips'] : null;
                $val = isset($item['value']) ? $item['value'] : null;
                if ($val === null) {
                    $val = $text;
                }
                $selected = strval($val) == strval($value) ? ' checked="checked"' : '';
                $out[] = '<label';
                if ($class !== null) {
                    $out[] = ' class="' . $class . '"';
                }
                if ($style !== null) {
                    $out[] = ' style="' . $style . '"';
                }
                $out[] = '>';
                if ($endKey === $key) {
                    $selected .= ' ' . join(' ', $attr);
                }
                $out[] = '<input type="radio" name="' . $name . '" value="' . htmlspecialchars($val) . '"' . $selected . '/>';
                $out[] = '<span>' . htmlspecialchars($text);
                if (!empty(strval($tips))) {
                    $out[] = '<em>' . htmlspecialchars($tips) . '</em>';
                }
                $out[] = '</span></label>' . "\n";
            }
        }
        return join('', $out);
    }

}