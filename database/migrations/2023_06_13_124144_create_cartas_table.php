<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cartas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->enum('tipo',['Contestación','Agradecimiento','Presentación','Iniciada']);
            $table->string('asunto')->nullable();
            $table->text('detalle')->nullable();
            $table->enum('estado',['Enviado','Respondida'])->default('Enviado');
            $table->string('archivo_imagen')->nullable();
            $table->string('archivo_pdf')->nullable();
            $table->string('archivo_imagen_ninio')->nullable();
            $table->string('archivo_familia_ninio')->nullable();
            $table->dateTime('fecha_respondida')->nullable();
            $table->foreignId('ninio_id')->constrained('ninios');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('comunidad_id')->constrained('comunidads');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartas');
    }
};
