<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'balance'  => 10000, // seed some USD for testing
        ]);

        // Give default assets (BTC = 10, ETH = 10) for testing
        $defaultAssets = [
            ['symbol' => 'BTC', 'amount' => 10, 'locked_amount' => 0],
            ['symbol' => 'ETH', 'amount' => 10, 'locked_amount' => 0],
        ];

        foreach ($defaultAssets as $asset) {
            \App\Models\Asset::create([
                'user_id' => $user->id,
                'symbol' => $asset['symbol'],
                'amount' => $asset['amount'],
                'locked_amount' => 0,
            ]);
        }

        return response()->json($user, 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        /** @var User|null $user */
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // No session here on API routes
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return $request->user();
    }
}
