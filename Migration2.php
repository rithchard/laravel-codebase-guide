<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración para crear la tabla `user_profiles`
 * 
 * Tabla de perfil extendido para usuarios. Almacena información
 * adicional opcional separada de los datos de autenticación.
 * Relación uno-a-uno con la tabla users.
 * 
 * @version 3.1.0
 */
return new class extends Migration
{
    private const TABLE_NAME = 'user_profiles';
    private const INDEX_PREFIX = 'idx_user_profiles_';
    private const FOREIGN_KEY_PREFIX = 'fk_user_profiles_';

    /**
     * Ejecutar la migración
     */
    public function up(): void
    {
        Schema::create(self::TABLE_NAME, function (Blueprint $table) {
            // ==================== CLAVE PRIMARIA Y FORÁNEA ====================
            
            /**
             * Clave primaria que también es foránea hacia users
             * Relación uno-a-uno (el perfil pertenece a un usuario)
             */
            $table->id()->comment('ID del usuario (clave foránea hacia users)');
            
            /**
             * Clave foránea hacia users con eliminación en cascada
             * Si se elimina el usuario, se elimina su perfil automáticamente
             */
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade')
                  ->comment('Referencia al usuario dueño de este perfil');
            
            // ==================== INFORMACIÓN DE PERFIL ====================
            
            $table->string('phone', 20)
                  ->nullable()
                  ->comment('Número de teléfono con código país')
                  ->index(self::INDEX_PREFIX . 'phone');
            
            $table->date('birth_date')
                  ->nullable()
                  ->comment('Fecha de nacimiento para verificación de edad');
            
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])
                  ->nullable()
                  ->comment('Género del usuario para personalización');
            
            $table->text('bio')
                  ->nullable()
                  ->comment('Biografía o descripción personal del usuario');
            
            $table->string('website')
                  ->nullable()
                  ->comment('Sitio web personal o portfolio');
            
            // ==================== DIRECCIÓN ====================
            
            $table->string('address_line_1', 255)
                  ->nullable()
                  ->comment('Línea 1 de dirección (calle y número)');
            
            $table->string('address_line_2', 255)
                  ->nullable()
                  ->comment('Línea 2 de dirección (departamento, piso)');
            
            $table->string('city', 100)
                  ->nullable()
                  ->comment('Ciudad de residencia');
            
            $table->string('state', 100)
                  ->nullable()
                  ->comment('Estado o provincia');
            
            $table->string('postal_code', 20)
                  ->nullable()
                  ->comment('Código postal');
            
            $table->string('country', 2)
                  ->nullable()
                  ->comment('Código de país ISO 3166-1 alpha-2')
                  ->index(self::INDEX_PREFIX . 'country');
            
            // ==================== CONFIGURACIONES ====================
            
            $table->json('preferences')
                  ->nullable()
                  ->comment('Preferencias de usuario en formato JSON');
            
            $table->boolean('is_public')
                  ->default(false)
                  ->comment('Indica si el perfil es público o privado');
            
            $table->boolean('accepts_marketing')
                  ->default(false)
                  ->comment('Consentimiento para marketing email');
            
            $table->string('timezone', 50)
                  ->default('UTC')
                  ->comment('Zona horaria del usuario');
            
            $table->string('locale', 10)
                  ->default('es')
                  ->comment('Idioma preferido (código ISO 639-1)');
            
            // ==================== METADATOS ====================
            
            $table->timestamps();
            
            /**
             * No usamos soft deletes aquí porque si el usuario se elimina,
             * el perfil se elimina en cascada automáticamente
             */
            
            // ==================== ÍNDICES ====================
            
            $table->index(['user_id', 'is_public'], self::INDEX_PREFIX . 'user_visibility');
            $table->index(['country', 'city'], self::INDEX_PREFIX . 'location');
            $table->index(['created_at'], self::INDEX_PREFIX . 'created_at');
        });
        
        // Comentario de tabla
        DB::statement("
            ALTER TABLE " . self::TABLE_NAME . " 
            COMMENT = 'Perfiles extendidos de usuarios. Información adicional opcional separada de datos de autenticación para mejor performance y seguridad.'
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE_NAME);
    }
};