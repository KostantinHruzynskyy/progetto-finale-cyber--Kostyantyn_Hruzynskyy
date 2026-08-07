<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Mostra il form di modifica del profilo
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Aggiorna il profilo utente
     * VULNERABILE: Permette mass assignment di tutti i campi fillable
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Validazione base (ma non blocca campi extra)
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // ❌ VULNERABILE: Mass assignment senza controlli
        // Un utente può inviare is_admin, is_revisor, is_writer nel form
        // e questi campi verranno aggiornati perché sono in $fillable
        $user->update($request->all());

        // Se la password è stata fornita, aggiornala
        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return redirect()->route('profile.edit')->with('message', 'Profilo aggiornato con successo');
    }
}