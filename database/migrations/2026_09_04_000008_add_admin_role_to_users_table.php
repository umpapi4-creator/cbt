<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    public function up(): void
    {
        // Target utama project ini MySQL/Laragon. Fresh install sudah memakai
        // enum yang memuat admin; ALTER diperlukan saat upgrade dari v0.10.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','lecturer','student') NOT NULL DEFAULT 'student'");
        }

        // Pastikan upgrade dari versi lama langsung memiliki satu akun admin
        // tanpa perlu menjalankan seeder ulang.
        $admin = DB::table('users')->where('email', 'admin@cbt.test')->first();
        if (!$admin) {
            DB::table('users')->insert([
                'name' => 'Admin CBT',
                'username' => 'tesadmin',
                'email' => 'admin@cbt.test',
                'password' => Hash::make('tesadmin'),
                'role' => 'admin',
                'student_number' => null,
                'lecturer_number' => null,
                'created_by_lecturer_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@cbt.test')->where('role', 'admin')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'admin')->update(['role' => 'student']);
            DB::statement("ALTER TABLE users MODIFY role ENUM('lecturer','student') NOT NULL DEFAULT 'student'");
        }
    }
};
