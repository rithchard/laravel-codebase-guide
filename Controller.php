<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * UserController - Controlador para gestión de usuarios
 * 
 * Maneja todas las operaciones CRUD para usuarios a través de API RESTful.
 * Implementa patrón Repository/Service para separación de concerns.
 * 
 * @group Gestión de Usuarios
 * @authenticated
 * 
 * @version 2.5.0
 * @package App\Http\Controllers\Api\V1
 * 
 * @responseFormat {
 *   "success": true,
 *   "data": {},
 *   "message": "Operación exitosa"
 * }
 * 
 * @author API Team <api@empresa.com>
 */
class UserController extends Controller
{
    /**
     * Constructor - Inyección de dependencias
     * 
     * @param UserService $userService Capa de servicio para lógica de negocio
     */
    public function __construct(
        private readonly UserService $userService
    ) {
        // Middleware específicos para el controlador
        $this->middleware('auth:sanctum')->except(['store']);
        $this->middleware('can:view users')->only(['index', 'show']);
        $this->middleware('can:create users')->only(['store']);
        $this->middleware('can:update users')->only(['update']);
        $this->middleware('can:delete users')->only(['destroy']);
    }

    /**
     * Listar todos los usuarios
     * 
     * Obtiene una lista paginada de usuarios con opciones de filtrado y búsqueda.
     * Incluye eager loading de relaciones para optimizar performance.
     * 
     * @urlParam page integer optional Página para paginación. Ejemplo: 1
     * @urlParam per_page integer optional Items por página (default: 15). Ejemplo: 20
     * @urlParam search string optional Término de búsqueda en nombre/email. Ejemplo: john
     * @urlParam verified boolean optional Filtrar por verificación de email. Ejemplo: true
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "data": [
     *       {
     *         "id": 1,
     *         "name": "John Doe",
     *         "email": "john@example.com",
     *         "created_at": "2025-01-15T10:30:00.000000Z"
     *       }
     *     ],
     *     "links": {
     *       "first": "http://api.example.com/users?page=1",
     *       "last": "http://api.example.com/users?page=5",
     *       "prev": null,
     *       "next": "http://api.example.com/users?page=2"
     *     },
     *     "meta": {
     *       "current_page": 1,
     *       "per_page": 15,
     *       "total": 75
     *     }
     *   },
     *   "message": "Usuarios obtenidos exitosamente"
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "No autenticado"
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "No autorizado para ver usuarios"
     * }
     * 
     * @param Request $request
     * @return UserCollection
     * 
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Request $request): UserCollection
    {
        // Autorización implícita via middleware
        // $this->authorize('viewAny', User::class);

        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $verified = $request->get('verified');

        // Construir query con scopes y eager loading
        $users = User::with(['profile'])
            ->when($search, fn($query) => $query->search($search))
            ->when(!is_null($verified), function ($query) use ($verified) {
                return $verified ? $query->verified() : $query->whereNull('email_verified_at');
            })
            ->latest()
            ->paginate($perPage);

        return new UserCollection($users);
    }

    /**
     * Almacenar nuevo usuario
     * 
     * Crea un nuevo usuario en el sistema con validación automática.
     * Dispara eventos de registro y envía email de verificación.
     * 
     * @bodyParam name string required Nombre del usuario. Ejemplo: John Doe
     * @bodyParam email string required Email único del usuario. Ejemplo: john@example.com
     * @bodyParam password string required Contraseña (mínimo 8 caracteres). Ejemplo: secret123
     * @bodyParam password_confirmation string required Confirmación de contraseña. Ejemplo: secret123
     * 
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "created_at": "2025-01-15T10:30:00.000000Z"
     *   },
     *   "message": "Usuario creado exitosamente"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Los datos proporcionados no son válidos",
     *   "errors": {
     *     "email": ["El email ya está en uso"],
     *     "password": ["La contraseña debe tener al menos 8 caracteres"]
     *   }
     * }
     * 
     * @param StoreUserRequest $request Request validado
     * @return JsonResponse
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        // La validación ya se realizó automáticamente via FormRequest
        $validatedData = $request->validated();

        // Delegar lógica de negocio al servicio
        $user = $this->userService->createUser($validatedData);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Usuario creado exitosamente'
        ], HttpResponse::HTTP_CREATED);
    }

    /**
     * Mostrar usuario específico
     * 
     * Obtiene los detalles completos de un usuario incluyendo relaciones.
     * 
     * @urlParam user integer required ID del usuario. Ejemplo: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "email_verified_at": "2025-01-15T10:30:00.000000Z",
     *     "profile": {
     *       "phone": "+1234567890",
     *       "address": "123 Main St"
     *     },
     *     "created_at": "2025-01-15T10:30:00.000000Z",
     *     "updated_at": "2025-01-15T10:30:00.000000Z"
     *   },
     *   "message": "Usuario obtenido exitosamente"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Usuario no encontrado"
     * }
     * 
     * @param User $user Modelo usuario (Route Model Binding)
     * @return JsonResponse
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function show(User $user): JsonResponse
    {
        // Cargar relaciones para respuesta completa
        $user->load(['profile', 'posts']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Usuario obtenido exitosamente'
        ]);
    }

    /**
     * Actualizar usuario existente
     * 
     * Actualiza la información de un usuario existente con validación.
     * 
     * @urlParam user integer required ID del usuario. Ejemplo: 1
     * @bodyParam name string optional Nombre del usuario. Ejemplo: Jane Doe
     * @bodyParam email string optional Email único del usuario. Ejemplo: jane@example.com
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "created_at": "2025-01-15T10:30:00.000000Z",
     *     "updated_at": "2025-01-15T11:30:00.000000Z"
     *   },
     *   "message": "Usuario actualizado exitosamente"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Los datos proporcionados no son válidos",
     *   "errors": {
     *     "email": ["El email ya está en uso"]
     *   }
     * }
     * 
     * @param UpdateUserRequest $request Request validado
     * @param User $user Modelo usuario a actualizar
     * @return JsonResponse
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validatedData = $request->validated();

        // Delegar actualización al servicio
        $updatedUser = $this->userService->updateUser($user, $validatedData);

        return response()->json([
            'success' => true,
            'data' => new UserResource($updatedUser),
            'message' => 'Usuario actualizado exitosamente'
        ]);
    }

    /**
     * Eliminar usuario (Soft Delete)
     * 
     * Realiza eliminación suave del usuario (soft delete).
     * El registro permanece en la base de datos pero marcado como eliminado.
     * 
     * @urlParam user integer required ID del usuario. Ejemplo: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": null,
     *   "message": "Usuario eliminado exitosamente"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Usuario no encontrado"
     * }
     * 
     * @param User $user Modelo usuario a eliminar
     * @return JsonResponse
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function destroy(User $user): JsonResponse
    {
        // Eliminación suave (soft delete)
        $this->userService->deleteUser($user);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }

    /**
     * Restaurar usuario eliminado
     * 
     * Restaura un usuario previamente eliminado (soft delete).
     * 
     * @urlParam user integer required ID del usuario. Ejemplo: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "message": "Usuario restaurado exitosamente"
     * }
     * 
     * @param int $id ID del usuario a restaurar
     * @return JsonResponse
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restore(int $id): JsonResponse
    {
        // Buscar usuario eliminado (withTrashed)
        $user = User::withTrashed()->findOrFail($id);
        
        $this->authorize('restore', $user);
        
        $user->restore();

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Usuario restaurado exitosamente'
        ]);
    }
}