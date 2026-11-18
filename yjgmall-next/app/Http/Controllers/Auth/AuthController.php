<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $ok = Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'));

        if (! $ok) {
            return back()->withErrors(['login' => '用户名或密码错误'])->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('member.index'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|alpha_dash|min:4|max:30|unique:users,username',
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|string|max:20|unique:users,mobile',
            'company_name' => 'nullable|string|max:100',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => 'pending',
            'points' => 20,
        ]);

        Auth::login($user);

        return redirect()->route('member.index')->with('success', '注册成功，账号待审核，部分功能可能受限');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
