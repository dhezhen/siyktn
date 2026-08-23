<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(
            ['email' => ['required', 'email']],
            attributes: ['email' => 'email']
        );

        Password::sendResetLink($request->only('email'));

        // Pesan sengaja disamakan agar tidak bisa dipakai menebak email terdaftar.
        return back()->with('info', 'Jika email tersebut terdaftar, tautan reset kata sandi telah kami kirim.');
    }
}
