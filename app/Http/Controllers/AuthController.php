<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('user_login', $request->login)->first();


        if (!$user || !Hash::check($request->password, $user->user_password)) {
            return response()->json(['message' => 'Identifiants incorrects'], 401);
        }

        $user->update(['user_date_login' => now()]);

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user
        ], 200);
    }
}
