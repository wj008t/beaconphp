<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace sdopx\compile;

/**
 * Description of Compile_Assign
 *
 * @author wj008
 */
class Compile_Assign extends CompileBase {

    public function compile($args, $compiler) {
        if (empty($args['var']) || empty($args['value'])) {
            return '';
        }
        if (preg_match('@^\w+$@', trim($args['var'], '\'"')) == 0) {
            return '';
        }
        $var = trim($args['var'], '\'"');
        return "<?php \$_sdopx->assign('{$var}',{$args['value']});?>";
    }

}
