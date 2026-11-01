<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
final class AccountController
{
    public function index() { return view('auth.account'); }
    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => ['required','current_password'], 'password' => ['required','confirmed','different:current_password','max:255',Password::min(8)->letters()->numbers()]]);
        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'session_version' => $user->session_version + 1, 'version' => $user->version + 1])->save();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $request->session()->regenerate(); $request->session()->put('session_version', $user->session_version);
        return back()->with('success', 'Password changed. Your other sessions have been signed out.');
    }
}
