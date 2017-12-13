<?php

namespace sdopx\compile;

class Compile_Literal extends CompileBase {

    /**
     * @param array $args
     * @param \sdopx\libs\Compile $compiler
     * @return string
     */
    public function compile($args, \sdopx\libs\Compile $compiler) {
        $left = empty($args['left']) ? '\'\'' : $args['left'];
        $right = empty($args['right']) ? '\'\'' : $args['right'];
        $_sdopx = $compiler->sdopx;
        eval("\$tpl_left = $left;\$tpl_right = $right;");
        if (empty($tpl_left) && empty($tpl_right)) {
            $tpl_left = '{@';
            $tpl_right = '@}';
        }
        if (empty($tpl_left)) {
            $compiler->trigger_template_error("left_delimiter not be empty", $compiler->parser->taglineno - 1);
        }
        if (empty($tpl_right)) {
            $compiler->trigger_template_error("right_delimiter not be empty", $compiler->parser->taglineno - 1);
        }
        $reg = '@' . preg_quote($compiler->source->left_delimiter, '@');
        $reg.=preg_quote('/literal') . '\s*';
        $reg.=preg_quote($compiler->source->right_delimiter, '@') . '@s';
        $compiler->source->end_literal = $reg;
        $this->openTag($compiler, 'literal', [$compiler->source->left_delimiter, $compiler->source->right_delimiter]);
        $compiler->source->left_delimiter = $tpl_left;
        $compiler->source->right_delimiter = $tpl_right;
        return null;
    }

}

class Compile_Literalclose extends CompileBase {

    public function compile($args, \sdopx\libs\Compile $compiler) {
        $compiler->source->end_literal = null;
        list($left, $right) = $this->closeTag($compiler, ['literal']);
        $compiler->source->left_delimiter = $left;
        $compiler->source->right_delimiter = $right;
        return null;
    }

}
