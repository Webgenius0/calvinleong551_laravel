<?php

namespace App\Http\Controllers\Web\Frontend;

use Exception;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SubscriberController extends Controller
{
    public function store(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email|unique:subscribers,email'
        ]);

        if (Subscriber::firstOrCreate([
            'email' => $validate['email']
        ])) {

            session()->put('t-success', 'Subscriber created successfully');
           
        } else {

            session()->put('t-error', 'Subscriber created failed!');
        }

        return redirect()->back();
        
    }

    public function index()
    {
        return view('frontend.layouts.privacy_policy');
    }
    public function Userlogin()
    {
        return view('frontend.layouts.user_login');
    }

    public function userProfile()
    {
        $user = Auth::user();
        return view('frontend.layouts.user_profile', compact('user'));
    }

    public function submitLogin(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login using Laravel Auth
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // Regenerate session to prevent fixation attacks
            $request->session()->regenerate();

            // Redirect to profile page
            return redirect()->intended('/user-profile');
        }

        return back()->with('error', 'Invalid email or password');
    }

    public function Userlogout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/sign-in');
    }


    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login.page')->with('error', 'User not found.');
        }

        // Optional: delete related data, e.g. posts, profile, etc.
        // $user->posts()->delete(); 

        // Delete the user
        $user->delete();

        // Logout after deletion
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/sign-in')->with('success', 'Your account has been deleted successfully.');
    }
    
}
