<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/15
 * Time: 21:34
 */

namespace widget;

use beacon\Field;
use beacon\Request;

class CheckGroup implements BoxInterface
{
    public function code(Field $field, $args)
    {

        $value = isset($args['value']) ? $args['value'] : $field->value;
        if ($value === null) {
            $value = $field->default;
        }
        if ($value != null || !is_array($value)) {
            $value = [];
        }
        $value = $this->convertType($value, 'string');

        $id = isset($args['id']) ? $args['id'] : $field->boxId;
        $name = isset($args['name']) ? $args['name'] : $field->boxName;
        $class = isset($args['class']) ? $args['class'] : $field->boxClass;
        $style = isset($args['style']) ? $args['style'] : $field->boxStyle;

        $options = isset($args['@options']) ? $args['@options'] : $field->options;
        $options = $options == null ? [] : $options;

        $args['value'] = '';
        $args['style'] = '';
        $args['class'] = '';
        $args['name'] = '';
        $args['type'] = '';
        $field->explodeAttr($attr, $args);
        $field->explodeData($attr, $args);

        $out = [];
        if ($field->useUlList) {
            $out[] = '<ul id="checkbox-group-' . $id . '"';
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
                $selected = in_array(strval($val), $value) ? ' checked="checked"' : '';
                $out[] = '<li><label>';
                if ($endKey === $key) {
                    $selected .= ' ' . join(' ', $attr);
                }
                $out[] = '<input type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($val) . '"' . $selected . '/>';
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
                $selected = in_array(strval($val), $value) ? ' checked="checked"' : '';
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
                $out[] = '<input type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($val) . '"' . $selected . '/>';
                $out[] = '<span>' . htmlspecialchars($text);
                if (!empty(strval($tips))) {
                    $out[] = '<em>' . htmlspecialchars($tips) . '</em>';
                }
                $out[] = '</span></label>' . "\n";
            }
        }
        return join('', $out);
    }

    private function convertType($values, $type)
    {
        if ($values === null) {
            return null;
        }
        foreach ($values as &$value) {
            switch ($type) {
                case 'bool':
                case 'boolean':
                    $value = strval($value) == '1' || strval($value) == 'on' || strval($value) == 'yes' || strval($value) == 'true';
                    break;
                case 'int':
                case 'integer':
                    $value = intval($value);
                    break;
                case 'double':
                case 'float':
                    $value = floatval($value);
                    break;
                default :
                    break;
            }
        }
        return $values;
    }

    public function assign(Field $field, array $data)
    {
        $request = $field->getForm()->context->getRequest();
        $default = [];
        if (is_array($field->default)) {
            $default = $field->default;
        }
        $field->value = $request->req($data, $field->boxName . ':a', $default);
        return $field->value;
    }

    public function fill(Field $field, array &$values)
    {
        $field->value = $field->value == null ? [] : $field->value;
        //处理按位填入数据库
        if ($field->bitComp) {
            $value = 0;
            if (is_array($field->value)) {
                foreach ($field->value as $item) {
                    if ((is_string($item) || is_integer($item)) && preg_match('@^\d+$@', $item)) {
                        $opt_value = intval($item);
                        $value = $value | $opt_value;
                    }
                }
            }
            $values[$field->name] = $value;
            return;
        }
        //处理按字段拆分填入数据值
        if (isset($field->names) && is_array($field->names)) {
            foreach ($field->names as $name) {
                $values[$name] = 0;
            }
            $options = $field->options == null ? [] : $field->options;
            $opts = [];
            foreach ($options as $item) {
                if (!is_array($item)) {
                    $opts[] = strval($item);
                } else {
                    if (isset($item['value'])) {
                        $opts[] = strval($item['value']);
                    } else if (isset($item['text'])) {
                        $opts[] = strval($item['text']);
                    }
                }
            }
            foreach ($field->names as $idx => $name) {
                $val = isset($opts[$idx]) ? $opts[$idx] : null;
                if ($val !== null && in_array($val, $field->value)) {
                    $values[$name] = 1;
                }
            }
            return;
        }
        if ($field->value === null) {
            $values[$field->name] = '';
            return;
        }
        $values[$field->name] = json_encode($field->value, JSON_UNESCAPED_UNICODE);
    }

    public function init(Field $field, array $values)
    {
        //按位解析出选项值
        if ($field->bitComp) {
            $value = isset($values[$field->name]) ? intval($values[$field->name]) : 0;
            $temps = [];
            $options = $field->options == null ? [] : $field->options;
            foreach ($options as $item) {
                $opt_value = '';
                if (!is_array($item)) {
                    $opt_value = strval($item);
                } else {
                    if (isset($item['value'])) {
                        $opt_value = strval($item['value']);
                    } else if (isset($item['text'])) {
                        $opt_value = strval($item['text']);
                    }
                }
                if (empty($opt_value) || !preg_match('@^\d+$@', $opt_value)) {
                    throw new Exception('使用位运算的选项值必须是数字形式。');
                }
                $temps[] = $value & intval($opt_value) > 0 ? 1 : 0;
            }
            return $field->value = $temps;
        }
        //按字段内容解析出选项值
        if (isset($field->names) && is_array($field->names)) {
            $options = $field->options == null ? [] : $field->options;
            $temp_values = [];
            $opts = [];
            foreach ($options as $item) {
                if (!is_array($item)) {
                    $opts[] = strval($item);
                } else {
                    if (isset($item['value'])) {
                        $opts[] = strval($item['value']);
                    } else if (isset($item['text'])) {
                        $opts[] = strval($item['text']);
                    }
                }
            }
            foreach ($field->names as $idx => $name) {
                $opt_value = isset($opts[$idx]) ? $opts[$idx] : null;
                if (isset($values[$name]) && intval($values[$name]) == 1) {
                    $temp_values[] = $opt_value;
                }
            }
            return $field->value = $temp_values;
        }
        $temps = isset($values[$field->name]) ? '' : $values[$field->name];
        if (is_array($temps)) {
            return $field->value = $temps;
        }
        if (Utils::isJsonString($temps)) {
            $temps = json_decode($temps);
            if (is_array($temps)) {
                return $field->value = $temps;
            }
        }
        return $field->value = null;
    }
}