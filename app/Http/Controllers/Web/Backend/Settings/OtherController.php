<?php

namespace App\Http\Controllers\Web\Backend\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class OtherController extends Controller
{
    public function index(){
        $settings = [
            'mail'       => env('MAIL', ''),
            'sms'        => env('SMS', ''),
            'recaptcha'  => env('RECAPTCHA_ENABLE', ''),
            'pagination' => env('PAGINATION', ''),
            'reverb'     => env('REVERB', ''),
            'debug'      => env('APP_DEBUG', ''),
            'access'     => env('ACCESS', '')
        ];
        return view('backend.layouts.settings.other_settings', compact('settings'));
    }
    
    public function mail(){
        if(env('MAIL') === 'off'){
            $envContent = str_replace('MAIL=off', 'MAIL=on', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }else{
            $envContent = str_replace('MAIL=on', 'MAIL=off', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }

        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!'
        ]);
    }

    public function sms(){
        if(env('SMS') === 'off'){
            $envContent = str_replace('SMS=off', 'SMS=on', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }else{
            $envContent = str_replace('SMS=on', 'SMS=off', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }

        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!'
        ]);
    }

    public function reverb(){
        if(env('REVERB') === 'off'){
            $envContent = str_replace('REVERB=off', 'REVERB=on', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }else{
            $envContent = str_replace('REVERB=on', 'REVERB=off', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }

        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!'
        ]);
    }

    public function recaptcha(){
        if(env('RECAPTCHA_ENABLE') === 'no'){
            $envContent = str_replace('RECAPTCHA_ENABLE=no', 'RECAPTCHA_ENABLE=yes', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }else{
            $envContent = str_replace('RECAPTCHA_ENABLE=yes', 'RECAPTCHA_ENABLE=no', file_get_contents(base_path('.env')));
            file_put_contents(base_path('.env'), $envContent);
        }

        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!'
        ]);
    }

    public function pagination(Request $request){
        $value = $request->input('value');
        $envContent = str_replace('PAGINATION='.env('PAGINATION'), 'PAGINATION='.$value, file_get_contents(base_path('.env')));
        file_put_contents(base_path('.env'), $envContent);

        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!'
        ]);
    }

    public function debug()
    {
        if(env('APP_DEBUG') === true) {
            $envContent = str_replace('APP_DEBUG=true', 'APP_DEBUG=false', file_get_contents(base_path('.env')));
        } else {
            $envContent = str_replace('APP_DEBUG=false', 'APP_DEBUG=true', file_get_contents(base_path('.env')));
        }

        file_put_contents(base_path('.env'), $envContent);

        Artisan::call('config:clear');

        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!'
        ], 200);
    }

    public function access()
    {
        if(env('ACCESS') === true) {
            $envContent = str_replace('ACCESS=true', 'ACCESS=false', file_get_contents(base_path('.env')));
        } else {
            $envContent = str_replace('ACCESS=false', 'ACCESS=true', file_get_contents(base_path('.env')));
        }

        file_put_contents(base_path('.env'), $envContent);

        Artisan::call('config:clear');

        return response()->json([
            'status' => 't-success',
            'message' => 'Your action was successful!'
        ], 200);
    }

}
