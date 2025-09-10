<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Listar usuarios con roles y permisos
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        try {
            $users = User::with('roles', 'permissions')->paginate($perPage);
            return response()->json([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudieron obtener los usuarios'
            ], 500);
        }
    }

    /**
     * Crear un usuario nuevo
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('user');

            return response()->json([
                'status' => 'success',
                'message' => 'Usuario creado correctamente',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear usuario: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo crear el usuario'
            ], 500);
        }
    }

    /**
     * Eliminar usuario
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->hasRole('admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede borrar un administrador'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Usuario eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error("Error eliminando usuario {$id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo eliminar el usuario'
            ], 500);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $authUser = auth()->user();

            if ($user->hasRole('admin') && !$authUser->hasRole('admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permisos para editar un administrador'
                ], 403);
            }

            $data = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:6',
            ]);

            // Validación de cambio de contraseña
            if (isset($data['password']) && !($authUser->id === $user->id || $authUser->hasRole('admin'))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No puedes cambiar la contraseña de otro usuario'
                ], 403);
            }

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Usuario actualizado correctamente',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Log::error("Error actualizando usuario {$id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo actualizar el usuario'
            ], 500);
        }
    }

    /**
     * Asignar permisos a un usuario
     */
    public function assignPermissions(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->hasRole('admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se pueden modificar permisos de un administrador'
                ], 403);
            }

            // Permitimos array vacío
            $permissions = $request->validate([
                'permissions' => 'nullable|array'
            ])['permissions'] ?? [];

            // Sincronizamos permisos (si es [] se quitan todos)
            $user->syncPermissions($permissions);

            return response()->json([
                'status' => 'success',
                'message' => 'Permisos asignados correctamente',
                'data' => $user->getAllPermissions()
            ]);
        } catch (\Exception $e) {
            Log::error("Error asignando permisos al usuario {$id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudieron asignar permisos'
            ], 500);
        }
    }
}