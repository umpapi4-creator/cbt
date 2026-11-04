<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('courses', function(Blueprint $table){$table->id();$table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();$table->string('code');$table->string('name');$table->unsignedTinyInteger('semester')->default(1);$table->timestamps();$table->unique(['lecturer_id','code']);}); } public function down(): void {Schema::dropIfExists('courses');} };
