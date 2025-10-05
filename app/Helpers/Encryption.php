<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;

if (!function_exists('encrypt')) {
    function encrypt($data)
    {
        $key = config('app.encryption_key');
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return Crypt::encryptString($data, $key);
    }
}

if (!function_exists('decrypt')) {
    function decrypt($data)
    {
        $key = config('app.encryption_key');
        return Crypt::decryptString($data, $key);
    }
}
