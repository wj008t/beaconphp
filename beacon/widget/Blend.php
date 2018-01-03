<?php
/**
 * Created by PhpStorm.
 * User: wj008
 * Date: 2017/12/20
 * Time: 0:40
 */

namespace widget {


    use beacon\Config;
    use beacon\Field;
    use beacon\Form;
    use beacon\Request;
    use beacon\Utils;

    class BlendViewer
    {
        private $form = null;
        /**
         * @var $fields  Field[]
         */
        public $fields = [];

        public $viewtplName = 'blend.tpl';

        public function __construct(Field $field, Form $form)
        {
            $this->form = $form;
            $this->fields = $form->getViewFields();
            $firstField = null;
            $field->plugType = isset($field->plugType) ? $field->plugType : 0;
            foreach ($this->fields as $name => $child) {
                if ($firstField == null) {
                    $firstField = $child;
                }
                if ($field->dataValOff) {
                    $child->dataValOff = true;
                }
                if (isset($field->labelWidth) && $field->labelWidth > 0 && $child->viewMerge == 0) {
                    $child->labelWidth = $field->labelWidth;
                }
                if ($field->plugType == 0 || $field->plugType == 1) {
                    $child->boxId = $field->boxId . '_' . $child->boxId;
                    $child->boxName = $field->boxName . '[' . $child->boxName . ']';
                } else {
                    $child->boxId = $field->boxId . '_@index@_' . $child->boxId;
                    $child->boxName = $field->boxName . '[@index@][' . $child->boxName . ']';
                }
            }
        }

        public function fetch()
        {
            $sdopx = new \sdopx\Sdopx();
            $common_dir = Utils::path(ROOT_DIR, 'view/widget');
            $sdopx->setTemplateDir($common_dir);
            $sdopx->assign('form', $this->form);
            return $sdopx->fetch($this->viewtplName);
        }

    }

    class Blend extends Hidden
    {

        public function code(Field $field, $args)
        {
            $class = $field->plugForm;
            if (empty($class) || !class_exists($field->plugForm)) {
                return '';
            }
            /**
             * @var $form Form
             */
            if ($field->plugType == 0 || $field->plugType == 1) {
                $form = new $class($field->getForm()->type);
                $form->initValues($field->value);
                $viewer = new BlendViewer($field, $form);
                return $viewer->fetch();
            } else {
                $form = new $class($field->getForm()->type);
                $viewer = new BlendViewer($field, $form);
                $out = [];
                $out[] = '<script type="text/code" id="' . $field->boxId . ':soures">';
                $out[] = $viewer->fetch();
                $out[] = '</script>';
                $args['type'] = 'hidden';
                $args['name'] = '';
                $args['yee-module'] = 'blend';
                $field->explodeAttr($attr, $args);
                $field->explodeData($attr, $args);
                $out[] = '<input ' . join(' ', $attr) . ' />';
                return join('', $out);
            }

        }

        public function assign(Field $field, array $data)
        {
            $boxName = $field->boxName;
            $request = $field->getForm()->context->getRequest();
            $fdata = $request->req($data, $boxName . ':a', $field->default);
            $class = $field->plugForm;
            if (empty($class) || !class_exists($field->plugForm)) {
                $field->value = null;
                return $field->value;
            }
            if ($field->plugType == 0 || $field->plugType == 1) {
                $form = new $class($field->getForm()->type);
                $form->autoComplete($fdata);
                $vdata = $form->getValues();
                $field->value = $vdata;
                return $field->value;
            }
            $temp = [];
            foreach ($fdata as $fidata) {
                $form = new $class($field->getForm()->type);
                $form->autoComplete($fdata);
                $vdata = $form->getValues();
                $temp[] = $vdata;
            }
            $field->value = $temp;
            return $field->value;
        }

        public function fill(Field $field, array &$values)
        {
            $values[$field->name] = json_encode($field->value, JSON_UNESCAPED_UNICODE);
        }

        public function init(Field $field, array $values)
        {
            $temp = isset($values[$field->name]) ? $values[$field->name] : null;
            if (Utils::isJsonString($temp)) {
                $field->value = json_decode($temp, true);
            }
        }

    }

}