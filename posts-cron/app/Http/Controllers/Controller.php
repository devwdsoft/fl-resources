<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public static function getData($url, $args = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                "REMOTE_ADDR: " . Controller::fakeIp(),
                "X-Client-IP: " . Controller::fakeIp(),
                "Client-IP: " . Controller::fakeIp(),
                "HTTP_X_FORWARDED_FOR: " . Controller::fakeIp(),
                "X-Forwarded-For: " . Controller::fakeIp(),
            )
        );
        if ($args) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        //curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1:8888");
        $result = curl_exec($ch);
        echo "VCL $result\n";
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_status != 200) {
            return null;
        }
        return $result;
    }

    private static function fakeIp()
    {
        return long2ip(mt_rand(0, 65537) * mt_rand(0, 65535));
    }
}
