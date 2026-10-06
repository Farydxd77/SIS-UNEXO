<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Pantalla "Mi perfil" (todos los roles).
 *
 * El usuario puede editar SOLO su telefono y su contrasena.
 * Nombre, apellidos, documento y correo institucional estan bloqueados:
 * son datos oficiales que salen en certificados y solo el admin los cambia.
 */
class PerfilController extends Controller
{
    public function show(): View
    {
        return view('perfil.show', [
            'usuario' => auth()->user()->load('roles'),
        ]);
    }

    /** Solo el telefono. Los demas campos ni se leen de la peticion. */
    public function actualizar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'telefono' => ['required', 'string', 'max:30'],
        ], [
            'telefono.required' => 'El telefono es obligatorio.',
        ]);

        auth()->user()->update($datos);

        return redirect()->route('perfil.show')
            ->with('exito', 'Telefono actualizado.');
    }

    /** Pantalla de cambio de contrasena (tambien la del primer ingreso, RN 1.7). */
    public function formularioPassword(): View
    {
        return view('perfil.password', [
            'obligatorio' => (bool) auth()->user()->debe_cambiar_password,
        ]);
    }

    public function actualizarPassword(Request $request): RedirectResponse
    {
        $usuario = auth()->user();

        $request->validate([
            // 'current_password' comprueba contra la contrasena real del usuario.
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password_actual.required' => 'Debes escribir tu contrasena actual.',
            'password_actual.current_password' => 'La contrasena actual no es correcta.',
            'password.required' => 'La nueva contrasena es obligatoria.',
            'password.confirmed' => 'Las contrasenas nuevas no coinciden.',
        ]);

        $usuario->update([
            'password' => $request->input('password'),
            // Cumplida la obligacion del primer ingreso.
            'debe_cambiar_password' => false,
        ]);

        return redirect()->route('dashboard')
            ->with('exito', 'Contrasena actualizada correctamente.');
    }
}
