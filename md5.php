<?php

if (!function_exists('md5')) {
    function md5($string) {
        return hash('md5', $string);
    }
}