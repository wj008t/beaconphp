<?php

namespace sdopx\compile;

class Compile_Extends extends CompileBase {

    /**
     * @param array $args
     * @param \sdopx\libs\Compile $compiler
     * @return string
     */
    public function compile($args, $compiler) {
        $name = $args['file'];
        $_sdopx = $compiler->sdopx;
        eval("\$tpl_name = $name;");
        $names = explode('|', $tpl_name);
        if (count($names) >= 2) {
            preg_replace('@^extends:@i', '', $tpl_name);
            $tpl_name = 'extends:' . $tpl_name;
        }
        $tpl = $compiler->tpl->createChildTemplate($tpl_name);
        $uid = $tpl->source->uid;
        if (isset($compiler->sdopx->extends_uid[$uid])) {
            $compiler->trigger_template_error("illegal recursive call of \"$tpl_name\"", $compiler->parser->taglineno - 1);
        }
        $compiler->sdopx->extends_uid[$uid] = true;
        $compiler->closed = true;
        return $tpl->compileTemplateSource();
    }

}
