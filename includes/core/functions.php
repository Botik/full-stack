<?php

// COMMON

function init_classes() {
    spl_autoload_register(function ($class_name) {
        include './includes/core/class_'.strtolower($class_name).'.php';
    });
}

function init_controllers_common() {
    $includes_dir = opendir('./includes/controllers_common');
    while (($inc_file = readdir($includes_dir)) != false) {
        if (strstr($inc_file, '.php')) require('./includes/controllers_common/'.$inc_file);
    }
}

function init_controllers_call() {
    $includes_dir = opendir('./includes/controllers_call');
    while (($inc_file = readdir($includes_dir)) != false) {
        if (strstr($inc_file, '.php')) require('./includes/controllers_call/'.$inc_file);
    }
}

function flt_input($var) {
    return str_replace(['\\', "\0", "'", '"', "\x1a", "\x00"], ['\\\\', '\\0', "\\'", '\\"', '\\Z', '\\Z'], $var);
}

function generate_rand_str($length, $type = 'hexadecimal') {
    // vars
    $str = '';
    if ($type == 'decimal') $chars = '0123456789';
    else if ($type == 'password') $chars = ['0123456789', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'];
    else $chars = 'abcdef0123456789';
    // generate
    for ($i = 0; $i < $length; $i++) {
        $microtime = round(microtime(true));
        if ($type != 'password') {
            srand($microtime + $i);
            $size = strlen($chars);
            $str .= $chars[rand(0, $size-1)];
        } else {
            $l = rand(-3, -1);
            $sub = substr($str, $l);
            if (!preg_match('~[0-9]~', $sub)) $chars_a = $chars[0];
            else if (!preg_match('~[A-Z]~', $sub)) $chars_a = $chars[1];
            else $chars_a = $chars[2];
            srand($microtime + $i);
            $size = strlen($chars_a);
            $str .= $chars_a[rand(0, $size-1)];
        }
    }
    // output
    return $str;
}

function error_response($code, $msg, $data = []) {
    $result['error_code'] = $code;
    $result['error_msg'] = $msg;
    if ($data) $result['error_data'] = $data;
    return $result;
}

function dump($data = []) {
    if (is_array($data)) $data = json_encode($data, JSON_UNESCAPED_UNICODE);
    error_log($data);
}

function phone_formatting($phone) {
    if (preg_match('~^[78]\d{10}$~', $phone)) $phone = preg_replace('~^([78])(\d{3})(\d{3})(\d{2})(\d{2})$~', '+$1 ($2) $3-$4-$5', $phone);
    return $phone;
}

function paginator($total, $offset, $q, $path, &$out) {
    if ($total > $q) {
        $m = 0;
        // digital links
        $k = $offset / $q;
        // not more than 4 links to the left
        $min = $k - 1;
        if ($min < 0) $min = 0;
        else {
            if ($min >= 1) {
                $out .= '<a href="/'.$path.'offset=0">1</a>';
                if ($min != 1) $out .= '&nbsp;&nbsp;...&nbsp;&nbsp;';
            }
        }
        for ($i = $min; $i < $k; $i++) {
            $m = $i*$q + $q;
            if ($m > $total) $m = $total;
            $out .= '<a href="/'.$path.'offset='.($i*$q).'">'.$m/$q.'</a>';
        }
        // # of current page
        $out .= '<a href="#" class="active">'.(($m/$q)+1).'</a>';
        // not more than 5 links to the right
        $min = $k + 2;
        if ($min > ceil($total/$q)) $min = ceil($total/$q);
        for ($i = $k + 1; $i < $min; $i++) {
            $m = $i * $q + $q;
            if ($m > $total) $m = $total;
            $out .= '<a href="/'.$path.'offset='.($i*$q).'">'.ceil($m/$q).'</a>';
        }
        if ($min * $q < $total) {
            if ($min * $q < $total-$q) $out .= '&nbsp;&nbsp;...&nbsp;&nbsp;';
            $out .= '<a href="/'.$path.'offset='.(($total-1)-($total-1)%$q).'">'.ceil($total/$q).'</a>';
        }
    }
}

/**
 * Get value from array by key and convert to integer
 *
 * @param array-key $arr
 * @param string $key
 * @param int $default
 *
 * @return int
 */
function get_int_array_key(mixed &$arr, string $key, int $default = 0): int {
    return isset($arr[$key]) && is_numeric($arr[$key]) ? (int) $arr[$key] : $default;
}

/**
 * Get value from array by key and convert tu trimmed string
 *
 * @param array-key $arr
 * @param string $key
 * @param string $default
 *
 * @return string
 */
function get_str_array_key(mixed &$arr, string $key, string $default = ''): string {
    return isset($arr[$key]) && trim($arr[$key]) ? trim($arr[$key]) : $default;
}

/**
 * Safe extract ints array from numbers string with comma separated
 *
 * @param string $str_numbers string with numbers with comma separated
 *
 * @return array<int>
 */
function extract_int_list(string $str_numbers): array {
    return array_reduce(
        explode(',', preg_replace('~[^,\d]+~', '', $str_numbers)),
        function ($acc, $item) {
            ($acc[] = (int) trim($item));

            return $acc;
        },
        []
    );
}
