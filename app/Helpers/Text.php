<?php
namespace App\Helpers;

if (!function_exists('parseTemplate')) {





/*
$template = "Hi {{ name }}, your order id is {{ order_id }}";
$array = ['name' => 'John Doe', 'order_id' => 1234]; 
$emailContent = parseTemplate($template, $array);
*/
    function parseTemplate($template, $array)
    {
        $value_array = [];
        foreach ($array as $key => $value) {
            $value_array['{{ ' . $key . ' }}'] = $value;
        }
        $text = str_replace(array_keys($value_array), array_values($value_array), $template);

        return $text;
    }

}