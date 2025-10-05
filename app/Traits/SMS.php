<?php

namespace App\Traits;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

trait SMS
{
    public function twilioSms($to, $message)
    {
        $sid = getenv("TWILIO_ACCOUNT_SID");
        $token = getenv("TWILIO_AUTH_TOKEN");
        $from = getenv("TWILIO_FROM_NUMBER");

        $twilio = new Client($sid, $token);
        $message = $twilio->messages
        ->create($to, // to
            array(
            "from" => $from,
            "body" => $message
            )
        );
    }

    public function bdSms($to, $message)
    {
        $url = "https://api.sms.net.bd/sendsms?api_key=".env('SMS_API_KEY')."&msg=".$message."&to=".$to;
        $response = Http::get($url);
        $response = $response->json();
       
        if($response['error'] == 421) {
            Log::info("SMS ERROR: ".$response['msg']);
        }
        return true;
    }
    
}
