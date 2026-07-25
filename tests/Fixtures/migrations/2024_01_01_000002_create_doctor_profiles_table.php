<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('license_number')->unique();
            $table->text('bio')->nullable();
            $table->integer('practice_since')->nullable();
            $table->string('website')->nullable();
            $table->timestamp('verification_date')->nullable();
            $table->boolean('is_accepting_new_patients')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->integer('years_of_experience')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
