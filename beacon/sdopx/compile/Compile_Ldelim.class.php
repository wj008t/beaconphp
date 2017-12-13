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
class Compile_Ldelim extends CompileBase {

    public function compile($args, $compiler) {
        return $compiler->source->left_delimiter;
    }

}
