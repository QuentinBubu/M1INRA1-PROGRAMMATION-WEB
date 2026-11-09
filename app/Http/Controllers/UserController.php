<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    // Afficher la liste des utilisateurs
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderByDesc('user_id')->get(),
        ]);
    }

    // Afficher le formulaire d'édition d'un utilisateur
    public function edit(int $user_id): View
    {
        return view('users.edit', [
            'user' => User::query()->findOrFail($user_id),
        ]);
    }

    // Mettre à jour l'utilisateur
    public function update(Request $request, int $user_id): RedirectResponse
    {
        $user = User::query()->findOrFail($user_id);

        // On valide uniquement ce qui est modifiable
        $validated = $request->validate([
            'user_login' => 'required|string|max:255|unique:user,user_login,' . $user_id . ',user_id',
            'user_mail' => 'required|email|max:255',
        ]);

        // Seuls ces champs seront modifiés en base, les dates restent intouchées
        $user->update($validated);

        return redirect('/admin/users')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    // Supprimer un utilisateur
    public function destroy(int $user_id): RedirectResponse
    {
        User::query()->findOrFail($user_id)->delete();

        return redirect('/admin/users')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
