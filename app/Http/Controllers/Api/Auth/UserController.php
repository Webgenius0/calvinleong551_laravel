<?php

namespace App\Http\Controllers\Api\Auth;

use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Account;
use App\Models\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'last_name', 'email', 'phone', 'dob', 'gender', 'slug', 'avatar', 'otp_verified_at'];
    }

    public function me()
    {
        $user = User::select($this->select)->find(auth('api')->user()->id);
        if (!$user) {
            return Helper::jsonResponse(false, 'User not found', 404);
        }

        // Add full_name attribute manually
        $user->full_name = trim($user->name . ' ' . $user->last_name);

        return Helper::jsonResponse(true, 'User details fetched successfully', 200, $user);
    }



    public function updateProfile(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'password' => 'nullable|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable',
            'gender' => 'nullable|in:male,female,others,non_binary',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else if (array_key_exists('password', $validatedData)) {
            unset($validatedData['password']);
        }

        // Convert DOB to MySQL compatible format
        // if (!empty($validatedData['dob'])) {
        //     try {
        //         $validatedData['dob'] = Carbon::createFromFormat('d-m-Y', $validatedData['dob'])->format('Y-m-d');
        //     } catch (\Exception $e) {
        //         return Helper::jsonResponse(false, 'Invalid date format for date of birth. Expected format: DD-MM-YYYY', 422);
        //     }
        // }


        if (!empty($validatedData['dob'])) {
            try {
                // Now expecting YYYY-MM-DD
                $validatedData['dob'] = Carbon::createFromFormat(
                    'Y-m-d',
                    $validatedData['dob']
                )->format('Y-m-d');
            } catch (\Exception $e) {
                return Helper::jsonResponse(
                    false,
                    'Invalid date format for date of birth. Expected format: YYYY-MM-DD',
                    422
                );
            }
        }

        $user = auth('api')->user();

        if ($request->hasFile('avatar')) {
            if (!empty($user->avatar)) {
                Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
            }
            $validatedData['avatar'] = Helper::fileUpload(
                $request->file('avatar'),
                'user/avatar',
                getFileName($request->file('avatar'))
            );
        } else {
            $validatedData['avatar'] = $user->avatar;
        }

        $user->update($validatedData);

        $data = User::select($this->select)->find($user->id);
        if ($data && $data->dob) {
            $data->dob = Carbon::parse($data->dob)->format('d-m-Y');
        }

        return Helper::jsonResponse(true, 'Profile updated successfully', 200, $data);
    }

    public function updateAvatar(Request $request)
    {
        $validatedData = $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);
        $user = auth('api')->user();
        if (!empty($user->avatar)) {
            Helper::fileDelete(public_path($user->getRawOriginal('avatar')));
        }
        $validatedData['avatar'] = Helper::fileUpload($request->file('avatar'), 'user/avatar', getFileName($request->file('avatar')));
        $user->update($validatedData);
        $data = User::select($this->select)->with('roles')->find($user->id);
        return Helper::jsonResponse(true, 'Avatar updated successfully', 200, $data);
    }

    public function delete()
    {
        $user = User::findOrFail(auth('api')->id());
        if (!empty($user->avatar) && file_exists(public_path($user->avatar))) {
            Helper::fileDelete(public_path($user->avatar));
        }
        Auth::logout('api');
        $user->delete();
        return Helper::jsonResponse(true, 'Profile deleted successfully', 200);
    }

    public function destroy()
    {
        $user = User::findOrFail(auth('api')->id());
        if (!empty($user->avatar) && file_exists(public_path($user->avatar))) {
            Helper::fileDelete(public_path($user->avatar));
        }
        Auth::logout('api');
        $user->forceDelete();
        return Helper::jsonResponse(true, 'Profile deleted successfully', 200);
    }



    public function changePassword(Request $request)
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return Helper::jsonResponse(false, 'User not found', 404, null);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'old_password'      => 'required',
            'new_password'      => 'required|min:6',
            'confirm_password'  => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return Helper::jsonResponse(false, 'Validation failed', 422, $validator->errors());
        }

        // Check if old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return Helper::jsonResponse(false, 'Old password does not match', 400, null);
        }

        // Update with new password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return Helper::jsonResponse(true, 'Password changed successfully', 200, null);
    }
}
