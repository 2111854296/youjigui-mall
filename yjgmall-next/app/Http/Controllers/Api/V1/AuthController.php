<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'member_type' => 'required|in:personal,company',
            'username' => 'required|alpha_dash|min:4|max:30|unique:users,username',
            'name' => 'required|string|max:50',
            'gender' => 'nullable|in:先生,女士,male,female',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|string|max:20|unique:users,mobile',
            'qq' => 'nullable|string|max:30',
            'area' => 'nullable|string|max:50',
            'company_name' => 'nullable|required_if:member_type,company|string|max:100',
            'company_type' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            ...collect($data)->except('password', 'password_confirmation')->all(),
            'password' => Hash::make($data['password']),
            'status' => 'pending',
            // 待审期间仍保留个人/企业身份，审核通过后 group_code 切到 personal/enterprise
            'group_code' => 'pending',
            'member_type' => $data['member_type'],
            'points' => 20,
            'level' => 0,
            'online_status' => 'online',
            'sound' => 1,
        ]);

        if ($data['member_type'] === 'company' && ! empty($data['company_name'])) {
            Company::create([
                'user_id' => $user->id,
                'name' => $data['company_name'],
                'slug' => Str::slug($data['company_name']).'-'.$user->id,
                'contact' => $data['name'],
                'phone' => $data['mobile'] ?? null,
                'area' => $data['area'] ?? null,
                'status' => 'pending',
            ]);
        }

        $token = $user->createToken('web')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $credentials['login'];
        $user = User::query()
            ->where(function ($q) use ($login) {
                $q->where('username', $login)
                    ->orWhere('email', $login)
                    ->orWhere('mobile', $login)
                    ->orWhere('company_name', $login);
            })
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => ['用户名或密码错误']]);
        }

        if ($user->isBanned()) {
            throw ValidationException::withMessages(['login' => ['账号已被禁止访问']]);
        }

        $user->forceFill(['online_status' => $user->online_status === 'invisible' ? 'invisible' : 'online'])->save();

        $token = $user->createToken('web')->plainTextToken;

        return [
            'data' => new UserResource($user),
            'token' => $token,
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'ok']);
    }

    public function me(Request $request)
    {
        return ['data' => new UserResource($request->user())];
    }
}
