<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * GET /login - Muestra el formulario.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * POST /login - Intenta iniciar sesion.
     */
    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        // Todo lo que no sea 'password' se convierte en un WHERE.
        // Asi un usuario con activo = 0 no puede entrar.
        $credenciales['activo'] = true;

        if (! Auth::attempt($credenciales, $request->boolean('recordarme'))) {
            // Mensaje generico a proposito: no revelamos si el fallo fue el
            // correo, la contraseña o que la cuenta esta desactivada.
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son correctas o la cuenta esta desactivada.',
            ]);
        }

        // Cambia el id de sesion tras entrar. Evita el "session fixation":
        // que alguien que conocia tu id de sesion anterior la reutilice.
        $request->session()->regenerate();

        // intended() vuelve a la pagina que el usuario queria antes del login.
        return redirect()->intended(route('dashboard'));
    }

    /**
     * POST /logout
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('exito', 'Sesion cerrada.');
    }
}
