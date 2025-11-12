<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla `users`
 * 
 * Esta migración define la estructura de la tabla principal de usuarios
 * del sistema, incluyendo campos para autenticación, verificación de email,
 * y timestamps automáticos. Implementa soft deletes para cumplir con GDPR.
 * 
 * @version 4.2.0
 * @package Database\Migrations
 * 
 * @table users
 * @engine InnoDB
 * @charset utf8mb4
 * @collation utf8mb4_unicode_ci
 * 
 * @author Database Team <db@empresa.com>
 * @copyright 2025 Empresa XYZ
 */
return new class extends Migration
{
    /**
     * Nombre de la tabla
     * 
     * @var string
     */
    private const TABLE_NAME = 'users';

    /**
     * Prefijo para índices
     * 
     * @var string
     */
    private const INDEX_PREFIX = 'idx_users_';

    /**
     * Prefijo para claves foráneas
     * 
     * @var string
     */
    private const FOREIGN_KEY_PREFIX = 'fk_users_';

    /**
     * Ejecutar la migración
     * 
     * Crea la estructura completa de la tabla de usuarios con:
     * - Campos esenciales para autenticación Laravel
     * - Índices optimizados para consultas frecuentes
     * - Restricciones de integridad referencial
     * - Comentarios para documentación de base de datos
     * 
     * @return void
     * 
     * @throws \Illuminate\Database\QueryException
     * 
     * @example
     * php artisan migrate --path=database/migrations/2025_01_15_000000_create_users_table.php
     */
    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table) {
            // ==================== COLUMNAS PRINCIPALES ====================
            
            /**
             * Identificador único auto-incremental
             * Tipo: BIGINT UNSIGNED (20)
             * Clave primaria
             */
            $table->id()->comment('Identificador único auto-incremental del usuario');
            
            // ==================== DATOS PERSONALES ====================
            
            /**
             * Nombre completo del usuario
             * Tipo: VARCHAR(255)
             * Nullable: NO
             * Index: Sí (búsquedas por nombre)
             */
            $table->string('name', 255)
                  ->comment('Nombre completo del usuario para mostrar')
                  ->index(self::INDEX_PREFIX . 'name');
            
            /**
             * Dirección de email única
             * Tipo: VARCHAR(255)
             * Nullable: NO
             * Unique: Sí (restricción única)
             * Index: Sí (búsquedas y logins)
             */
            $table->string('email', 255)
                  ->unique()
                  ->comment('Dirección de email única para autenticación y comunicación')
                  ->index(self::INDEX_PREFIX . 'email');
            
            // ==================== SEGURIDAD Y AUTENTICACIÓN ====================
            
            /**
             * Hash de contraseña (bcrypt)
             * Tipo: VARCHAR(255)
             * Nullable: NO
             * Algoritmo: Bcrypt (Laravel default)
             */
            $table->string('password')
                  ->comment('Hash de contraseña usando algoritmo bcrypt');
            
            /**
             * Fecha de verificación de email
             * Tipo: TIMESTAMP
             * Nullable: SÍ
             * Index: Sí (filtrar usuarios verificados)
             */
            $table->timestamp('email_verified_at')
                  ->nullable()
                  ->comment('Fecha y hora cuando el usuario verificó su dirección de email')
                  ->index(self::INDEX_PREFIX . 'email_verified');
            
            /**
             * Token para funcionalidad "recordar sesión"
             * Tipo: VARCHAR(100)
             * Nullable: SÍ
             */
            $table->rememberToken()
                  ->comment('Token para mantener la sesión activa entre visitas');
            
            // ==================== METADATOS Y TIMESTAMPS ====================
            
            /**
             * Timestamps automáticos de Laravel
             * created_at: Fecha de creación del registro
             * updated_at: Fecha de última actualización
             */
            $table->timestamps();
            
            /**
             * Soft delete timestamp
             * Tipo: TIMESTAMP
             * Nullable: SÍ
             * Implementa eliminación suave (GDPR compliance)
             */
            $table->softDeletes()
                  ->comment('Fecha y hora de eliminación suave (soft delete) para cumplimiento GDPR')
                  ->index(self::INDEX_PREFIX . 'deleted_at');
            
            // ==================== ÍNDICES COMPUESTOS ====================
            
            /**
             * Índice compuesto para búsquedas por email y estado
             * Optimiza consultas que filtran por email y eliminación
             */
            $table->index(
                ['email', 'deleted_at'], 
                self::INDEX_PREFIX . 'email_status'
            );
            
            /**
             * Índice compuesto para reportes por fecha creación
             * Optimiza consultas de analytics y reportes
             */
            $table->index(
                ['created_at', 'deleted_at'], 
                self::INDEX_PREFIX . 'creation_status'
            );
            
            /**
             * Índice compuesto para usuarios activos recientes
             * Optimiza dashboard y listados de usuarios activos
             */
            $table->index(
                ['email_verified_at', 'deleted_at', 'created_at'],
                self::INDEX_PREFIX . 'active_users'
            );
        });
        
        // ==================== COMENTARIOS DE TABLA ====================
        
        /**
         * Comentario de tabla para documentación en BD
         * Visible en herramientas como phpMyAdmin, MySQL Workbench
         */
        DB::statement("
            ALTER TABLE " . self::TABLE_NAME . " 
            COMMENT = 'Tabla maestra de usuarios del sistema. Almacena información de autenticación, perfiles básicos y metadatos de usuarios registrados en la plataforma.'
        ");
        
        // ==================== OPTIMIZACIONES AVANZADAS ====================
        
        /**
         * Configuración de collation para búsquedas case-insensitive
         * utf8mb4_unicode_ci: Soporte completo Unicode, case-insensitive
         */
        DB::statement("
            ALTER TABLE " . self::TABLE_NAME . " 
            CONVERT TO CHARACTER SET utf8mb4 
            COLLATE utf8mb4_unicode_ci
        ");
    }

    /**
     * Revertir la migración
     * 
     * Elimina completamente la tabla de usuarios y todos sus índices.
     * Se ejecuta automáticamente cuando se rollback la migración.
     * 
     * @return void
     * 
     * @warning Esta operación elimina permanentemente todos los datos de usuarios
     * @danger No ejecutar en producción sin backup
     * 
     * @example
     * php artisan migrate:rollback --step=1
     */
    public function down(): void
    {
        /**
         * Eliminar claves foráneas primero (si existieran)
         * Previene errores de integridad referencial
         */
        Schema::table(self::TABLE_NAME, function (Blueprint $table) {
            // Ejemplo: Si existieran claves foráneas hacia otras tablas
            // $table->dropForeign(self::FOREIGN_KEY_PREFIX . 'role_id');
        });
        
        /**
         * Eliminar la tabla completamente
         * Incluye todos los datos, índices y restricciones
         */
        Schema::dropIfExists(self::TABLE_NAME);
        
        /**
         * Log de eliminación (solo desarrollo)
         */
        if (app()->environment('local', 'testing')) {
            info('Tabla ' . self::TABLE_NAME . ' eliminada exitosamente');
        }
    }
};