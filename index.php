<?php
use IEXBase\TronAPI\Exception\TronException;

header("Content-Type: Text/Html;Charset=UTF-8");
require "./vendor/autoload.php";

try {
    $tron_obj = new \think\BlockChain();
    $result = $tron_obj->generateAddress();
    echo '<pre/>';
    print_r($result);
} catch (TronException $e) {
    echo $e->getMessage();
} catch (\Exception $e) {
    echo $e->getMessage();
}

