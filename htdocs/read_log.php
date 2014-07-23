<?php

/**
 * script to read messages written by mri_upload
 *
 * PHP version 5
 *
 * @category Script
 * @package  Main
 * @author   Olga Tsibulevskaya  <olgatsib@gmail.com>
 * @license  Loris License
 * @link     https://github.com/aces/Loris-Trunk
*/


header("Cache-Control: no-store, no-cache, must-revalidate");
$data_source_file = "/data/.log";

while (true) {
    clearstatcache();
    if (file_get_contents($data_source_file) === '') {
        sleep(1);
        continue;
    }
    try {
        $file = fopen($data_source_file, "r+");
        if (! $file) {
            throw new Exception("Could not open the log file!");
        } else {
            flock($file, LOCK_EX); 
            $data = file_get_contents($data_source_file);
            ftruncate($file, 0);
            flock($file, LOCK_UN);
            fclose($file);
        }
    } catch(Exception $e) {
        $data = "Error: " . $e->getMessage();
    }   
    echo $data;
    ob_flush();
    flush();
    break;
}
?>
