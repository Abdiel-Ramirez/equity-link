<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login de usuario
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Credenciales incorrectas'
                ], 401);
            }

            $token = $this->createAuthToken($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Login exitoso',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $this->getRolesAndPermissions($user),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout de usuario
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logout exitoso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al cerrar sesión: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuario autenticado
     */
    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->getRolesAndPermissions($user)
        ]);
    }

    /**
     * Crear token Sanctum para un usuario
     */
    private function createAuthToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Obtener roles y permisos de un usuario
     */
    private function getRolesAndPermissions(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(), // devuelve colección de strings
            'permissions' => $user->getAllPermissions()->pluck('name') // colección de strings
        ];
    }
}
