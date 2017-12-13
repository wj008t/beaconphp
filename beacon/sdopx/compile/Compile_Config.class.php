<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace sdopx\compile;

/**
 * Description of Compile_Config
 *
 * @author wj008
 */
class Compile_Config {

    public function compile($code, $compiler) {
        return "<?php echo sdopx_tpl_config('{$code}',\$_sdopx);?>";
    }

}
