<?php

/**
 * Description of myLogWriterClass
 *
 * @author Kaiser Waseem
 */

namespace Logilim;

class myLoggerClass {

    public static function logIt($params) {
        $separator = "\n------------------" . date("Y-m-d h:i:s") . "------------------\n" . $params['type'] . "\n";
        $fp = fopen("/var/www/resources/fd-logs/" . date("Y-m-d") . ".txt", "a");
        @fwrite($fp, $separator . $params['contents']);
        fclose($fp);

//        $log = new stdClass();
//        $log->date = gmdate("Y-m-d\TH:i:s\Z"); //date("Y-m-d h:i:s")
//        $log->type = $params['type'];
//        $log->jid = $params['jid'];
//        $log->msg = @iconv("utf-8", "ISO-8859-1//TRANSLIT", $params['contents']);
    }

}
