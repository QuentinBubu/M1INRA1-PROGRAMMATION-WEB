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


    public function register(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string|max:255|unique:user,user_login',
            'email' => 'required|email|max:255',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'login.required' => 'L’identifiant est obligatoire.',
            'login.unique' => 'Cet identifiant est déjà utilisé.',
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'L’adresse e-mail est invalide.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.regex' => 'Le mot de passe doit contenir une majuscule, une minuscule et un chiffre.',
        ]);

        $nextCompteId = ((int) User::max('user_compte_id')) + 1;

        $user = User::create([
            'user_login' => $validated['login'],
            'user_password' => Hash::make($validated['password']),
            'user_compte_id' => $nextCompteId,
            'user_mail' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'user' => $user,
        ], 201);
    }
}
