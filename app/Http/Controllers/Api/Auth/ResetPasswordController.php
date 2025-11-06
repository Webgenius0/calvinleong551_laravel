<?php

namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Mail\OtpMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'email', 'avatar'];   
    }
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        try {
            $email = $request->input('email');
            $otp   = rand(1000, 9999);
            $user  = User::where('email', $email)->first();

            if ($user) {
                Mail::to($email)->send(new OtpMail($otp, $user, 'Reset Your Password'));

                $user->otp            = $otp;
                $user->otp_expires_at = Carbon::now()->addMinutes(60);
                $user->save();

                return response()->json([
                    'status'  => true,
                    'message' => 'OTP sent to your email.',
                    'code'    => 200,
                    'otp'    => $otp,
                ]);
            } else {
                return Helper::jsonErrorResponse('Invalid Email Address', 404);
            }

        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }

    public function MakeOtpToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|digits:4',
        ]);
        
        try {
            $email = $request->input('email');
            $otp   = $request->input('otp');
            $user = User::where('email', $email)->first();

            if (!$user) {
                return Helper::jsonErrorResponse( 'User not found', 404);
            }

            if (Carbon::parse($user->otp_expires_at)->isPast()) {
                return Helper::jsonErrorResponse('OTP has expired.', 400);
            }

            if ($user->otp !== $otp) {
                return Helper::jsonErrorResponse('Invalid OTP', 400);
            }
            $token = Str::random(60);

            $user->otp = null;
            $user->otp_expires_at = null;
            $user->reset_password_token = $token;
            $user->reset_password_token_expire_at = Carbon::now()->addHour();

            $user->save();

            return response()->json([
                'status'     => true,
                'message'    => 'OTP verified successfully.',
                'code'       => 200,
                'token'      => $token,
            ]);
        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }


    public function ResetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);
        try {
            $email       = $request->input('email');
            $newPassword = $request->input('password');

            $user = User::where('email', $email)->first();
            if (!$user) {
                return Helper::jsonErrorResponse( 'User not found', 404);
            }

            if (!empty($user->reset_password_token) && $user->reset_password_token === $request->token && $user->reset_password_token_expire_at >= Carbon::now()) {
                
                $user->password = Hash::make($newPassword);
                $user->reset_password_token = null;
                $user->otp = null;
                $user->otp_expires_at = null;
                $user->reset_password_token_expire_at = null;

                $user->save();

                return Helper::jsonResponse(true, 'Password reset successfully.', 200);
            }else{
                return Helper::jsonErrorResponse('Invalid Token', 419);
            }

        } catch (Exception $e) {
            return Helper::jsonErrorResponse($e->getMessage(), 500);
        }
    }
}
