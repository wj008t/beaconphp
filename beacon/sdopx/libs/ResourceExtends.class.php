<?php

namespace sdopx\libs {

    class ResourceExtends
    {

        private function getResource($tplname, $sdopx)
        {
            Resource::parseResourceName($tplname, 'file', $name, $type);
            $_resource_class = '\\sdopx\\libs\\Resource' . ucfirst($type);
            if (!class_exists($_resource_class)) {
                throw new \sdopx\SdopxException('没有找到 ' . $type . ' 类型的源数据。');
            }
            $_resource = new $_resource_class();
            $filepath = $name;
            if ($type == 'file') {
                if ($name != '' && $name[0] == '@') {
                    $name = $sdopx->getTemplateDir('common') . substr($name, 1);
                }

                if (file_exists($name)) {
                    $filepath = realpath($name);
                } else {
                    $tempdir = $sdopx->getTemplateDir();
                    foreach ($tempdir as $path) {
                        $filename = Utils::path($path, $name);
                        if (file_exists($filename)) {
                            $filepath = realpath($filename);
                            break;
                        }
                    }
                    if ($filepath == null) {
                        throw new \sdopx\SdopxException("Sdopx 已经翻遍整个模板目录也没有找到编译所需的模板文件 {$tplname} .");
                    }
                }
            }

            return [$_resource, $name, $type, $filepath];
        }

        public function fetch($tplname, &$content, &$timestamp, $sdopx)
        {
            $names = explode('|', $tplname);
            if (count($names) < 2) {
                throw new \sdopx\SdopxException("Sdopx 解析母版继承错误{$tplname} .");
            }
            $tplchild = array_pop($names);
            $extends = join('|', $names);
            list($_resource, $name, $type, $filepath) = $this->getResource($tplchild, $sdopx);
            $rcontent = '';
            $rtimestamp = 0;
            if ($type == 'file') {
                $_resource->fetch($filepath, $rcontent, $rtimestamp, $sdopx);
            } else {
                $_resource->fetch($name, $rcontent, $rtimestamp, $sdopx);
            }
            $content = $sdopx->left_delimiter . 'extends file=\'' . $extends . '\'' . $sdopx->right_delimiter . $rcontent;
            $timestamp = $rtimestamp;
        }

        public function fetchTimestamp($tplname, $sdopx)
        {
            $names = explode('|', $tplname);
            if (count($names) < 2) {
                throw new \sdopx\SdopxException("Sdopx 解析母版继承错误{$tplname} .");
            }
            $tplchild = end($names);
            list($_resource, $name, $type, $filepath) = $this->getResource($tplchild, $sdopx);
            if ($type == 'file') {
                return $_resource->fetchTimestamp($filepath, $sdopx);
            } else {
                return $_resource->fetchTimestamp($name, $sdopx);
            }
        }

    }

}