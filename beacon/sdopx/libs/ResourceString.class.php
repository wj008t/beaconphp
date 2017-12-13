<?php

namespace sdopx\libs {

    class ResourceString
    {

        public function fetch($tplname, &$content, &$timestamp)
        {
            $tplname = urldecode($tplname);
            $content = $tplname;
            $timestamp = time();
        }

        public function fetchTimestamp($tplname, $sdopx)
        {
            $tplname = urldecode($tplname);
            if ($tplname === null) {
                return time();
            }
            return 1;
        }

    }

}