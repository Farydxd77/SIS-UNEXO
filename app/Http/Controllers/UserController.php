<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * MODULO 1 - CRUD de usuarios. Solo administrador.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->query('buscar');
        $rolId = $request->query('rol');
        $estado = $request->query('estado');

        $usuarios = User::query()
            // Evita el N+1: los roles de todos en una sola consulta extra.
            ->with('roles')
            ->when($buscar, function ($query, $buscar) {
                // El where anidado agrupa los OR entre parentesis para que
                // no se coman los otros filtros.
                $query->where(function ($q) use ($buscar) {
                    $q->whereLike('name', "%{$buscar}%")
                        ->orWhereLike('apellidos', "%{$buscar}%")
                        ->orWhereLike('documento', "%{$buscar}%")
                        ->orWhereLike('email', "%{$buscar}%")
                        ->orWhereLike('correo_institucional', "%{$buscar}%");
                });
            })
            ->when($rolId, fn ($query, $rolId) => $query->whereHas('roles', fn ($q) => $q->where('roles.id', $rolId)))
            ->when($estado !== null && $estado !== '', fn ($query) => $query->where('activo', $estado === '1'))
            ->orderBy('apellidos')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => Rol::orderBy('nombre')->get(),
            'buscar' => $buscar,
            'rolId' => $rolId,
            'estado' => $estado,
        ]);
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        // Los roles no son columna de users: van en la tabla pivote rol_user.
        $roles = $datos['roles'] ?? [];
        unset($datos['roles']);

        // RN 1.7: la contrasena que carga el admin es temporal.
        $datos['debe_cambiar_password'] = true;

        $usuario = User::create($datos);
        $usuario->roles()->sync($roles);

        return redirect()
            ->route('usuarios.index')
            ->with('exito', "Usuario \"{$usuario->nombre_completo}\" creado. Debera cambiar su contrasena al primer ingreso.");
    }

    public function show(User $usuario): View
    {
        $usuario->load(['roles', 'gruposComoDocente.modulo', 'matriculas.grupo.modulo']);

        return view('usuarios.show', compact('usuario'));
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => Rol::orderBy('nombre')->get(),
            'rolesDelUsuario' => $usuario->roles->pluck('id')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        $roles = $datos['roles'] ?? [];
        unset($datos['roles']);

        // Si el campo llega vacio, se conserva la contrasena actual.
        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        } else {
            // Contrasena puesta por el admin: temporal otra vez.
            $datos['debe_cambiar_password'] = true;
        }

        // RN 1.5: no se puede quitar el rol al ultimo administrador activo.
        if ($error = $this->violaReglaUltimoAdmin($usuario, $roles, $datos['activo'] ?? $usuario->activo)) {
            return back()->withInput()->with('error', $error);
        }

        $usuario->update($datos);
        $usuario->roles()->sync($roles);

        return redirect()
            ->route('usuarios.index')
            ->with('exito', "Usuario \"{$usuario->nombre_completo}\" actualizado.");
    }

    /**
     * RN 1.6: la baja es siempre logica. Nunca DELETE.
     */
    public function destroy(User $usuario): RedirectResponse
    {
        // RN 1.4: el admin no puede desactivarse a si mismo.
        if ($usuario->id === auth()->id()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        // RN 1.5 aplicada a la desactivacion: no dejar el sistema sin admin.
        if ($usuario->esAdministrador() && User::otrosAdministradoresActivos($usuario->id) === 0) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No se puede desactivar al ultimo administrador activo del sistema.');
        }

        $usuario->update(['activo' => false]);

        return redirect()->route('usuarios.index')
            ->with('exito', "Usuario \"{$usuario->nombre_completo}\" desactivado. Su historial se conserva.");
    }

    public function activar(User $usuario): RedirectResponse
    {
        $usuario->update(['activo' => true]);

        return redirect()->route('usuarios.index')
            ->with('exito', "Usuario \"{$usuario->nombre_completo}\" reactivado.");
    }

    /**
     * Restablecer contrasena: genera una temporal y obliga a cambiarla.
     * Se muestra una sola vez al admin para que se la comunique al usuario.
     */
    public function restablecerPassword(User $usuario): RedirectResponse
    {
        $temporal = Str::password(10, symbols: false);

        $usuario->update([
            'password' => $temporal,
            'debe_cambiar_password' => true,
        ]);

        return redirect()->route('usuarios.show', $usuario)
            ->with('exito', "Contrasena restablecida. Temporal para {$usuario->nombre_completo}: {$temporal}")
            ->with('password_temporal', $temporal);
    }

    /**
     * Devuelve el mensaje de error si la operacion dejaria al sistema
     * sin ningun administrador activo; null si todo bien.
     */
    private function violaReglaUltimoAdmin(User $usuario, array $rolesNuevos, bool $activo): ?string
    {
        if (! $usuario->esAdministrador()) {
            return null;
        }

        $idAdmin = Rol::where('nombre', User::ROL_ADMINISTRADOR)->value('id');
        $sigueSiendoAdmin = in_array((string) $idAdmin, array_map('strval', $rolesNuevos), true);

        if ($sigueSiendoAdmin && $activo) {
            return null;
        }

        if (User::otrosAdministradoresActivos($usuario->id) > 0) {
            return null;
        }

        return 'No se puede quitar el rol de administrador ni desactivar al ultimo administrador activo del sistema.';
    }
}
