<?php

namespace sdopx\libs {

    class ResourceFile {

        public function fetch($tplname, &$content, &$timestamp) {
            $content = file_get_contents($tplname);
            $timestamp = @filemtime($tplname);
        }

        public function fetchTimestamp($tplname) {
            return @filemtime($tplname);
        }

    }

}