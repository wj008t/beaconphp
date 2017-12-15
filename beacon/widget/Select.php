<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/14
 * Time: 18:02
 */

namespace widget;


use beacon\Field;

class Select extends Hidden
{

    public function code(Field $field, $args)
    {
        if (isset($args['value'])) {
            $field->value = $args['value'];
        }

        $options = isset($args['@options']) ? $args['@options'] : $field->options;
        $options = $options == null ? [] : $options;

        $args['value'] = '';
        $args['type'] = '';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);

        $out = [];
        $out[] = '<select ' . join(' ', $attr) . '>' . "\n";
        if ($field->header !== null) {
            if (is_string($field->header)) {
                $out[] = '<option value="">';
                $out[] = htmlspecialchars($field->header);
                $out[] = '</option>';
            } else if (is_array($field->header) && isset($field->header['text'])) {
                if (isset($field->header['value'])) {
                    $out[] = '<option value="' . htmlspecialchars($field->header['value']) . '">';
                } else {
                    $out[] = '<option value="">';
                }
                $out[] = htmlspecialchars($field->header['text']);
                $out[] = '</option>';
            }
        }
        foreach ($options as $item) {
            if ($item == null) {
                continue;
            }
            if (!is_array($item)) {
                $item = ['value' => $item];
            }
            $text = isset($item['text']) ? $item['text'] : null;
            $tips = isset($item['tips']) ? $item['tips'] : null;
            $group = isset($item['group']) ? $item['group'] : null;
            $val = isset($item['value']) ? $item['value'] : null;
            if ($val === null) {
                $val = $text;
            }
            if ($group !== null && is_array($group)) {
                $out[] = '<optgroup';
                if ($text !== null) {
                    $out[] = ' label="' . htmlspecialchars($text) . '"';
                }
                $out[] = '>' . "\n";
                foreach ($group as $gitem) {
                    if (!is_array($item)) {
                        $gitem = ['value' => $gitem];
                    }
                    $gtext = isset($gitem['text']) ? $gitem['text'] : null;
                    $gtips = isset($gitem['tips']) ? $gitem['tips'] : null;
                    $gval = isset($gitem['value']) ? $gitem['value'] : null;
                    if ($gval === null) {
                        $gval = $gtext;
                    }
                    $selected = strval($gval) == strval($field->value) ? ' selected="selected"' : '';
                    $out[] = '  <option value="' . htmlspecialchars($gval) . '"' . $selected . '>';
                    $out[] = htmlspecialchars($gtext);
                    if (!empty($gtips)) {
                        $out[] = ' | ' . htmlspecialchars($gtips);
                    }
                    $out[] = '</option>' . "\n";
                }
                $out[] = '</optgroup>' . "\n";
                continue;
            }
            $selected = strval($val) == strval($field->value) ? ' selected="selected"' : '';
            $out[] = '<option value="' . htmlspecialchars($val) . '"' . $selected . '>';
            $out[] = htmlspecialchars($text);
            if (!empty($tips)) {
                $out[] = ' | ' . htmlspecialchars($tips);
            }
            $out[] = '</option>' . "\n";
        }
        $out[] = '</select>';
        return join('', $out);
    }

}