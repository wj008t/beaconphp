<?php

namespace sdopx\compile {

    class Compile_If extends CompileBase {

        public function compile($args, $compiler) {
            $this->openTag($compiler, 'if');
            return "<?php if({$args['code']}) { ?>";
        }

    }

    class Compile_Else extends CompileBase {

        public function compile($args, $compiler) {
            $this->closeTag($compiler, ['if', 'elseif']);
            $this->openTag($compiler, 'else');
            return '<?php }else{ ?>';
        }

    }

    class Compile_Elseif extends CompileBase {
        public function compile($args, $compiler) {
            $this->closeTag($compiler, ['if', 'elseif']);
            $this->openTag($compiler, 'elseif');
            return "<?php } elseif({$args['code']}){ ?>";
        }
    }

    class Compile_Ifclose extends CompileBase {

        public function compile($args, $compiler) {
            $this->closeTag($compiler, ['if', 'else', 'elseif']);
            return "<?php }?>";
        }

    }

}