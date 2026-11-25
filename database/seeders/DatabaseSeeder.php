<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\MataKuliah;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Dosen Pengampu Default
        $dosen = User::updateOrCreate(
            ['email' => 'dosen@celoe.test'],
            [
                'name' => 'Dr. Ir. Budi Santoso, M.T.',
                'password' => Hash::make('dosen123'),
                'role' => 'dosen',
                'nomor_induk' => '198501012015041001',
            ]
        );

        // 2. Akun Mahasiswa Default
        $mahasiswa1 = User::updateOrCreate(
            ['email' => 'mahasiswa@celoe.test'],
            [
                'name' => 'Ahmad Pratama',
                'password' => Hash::make('mahasiswa123'),
                'role' => 'mahasiswa',
                'nomor_induk' => '1301210001',
            ]
        );

        // 2b. Mahasiswa tambahan
        $mahasiswa2 = User::updateOrCreate(
            ['email' => 'mahasiswa2@celoe.test'],
            [
                'name' => 'Siti Rahmawati',
                'password' => Hash::make('mahasiswa123'),
                'role' => 'mahasiswa',
                'nomor_induk' => '1301210002',
            ]
        );

        // 3. Mata Kuliah (3 mata kuliah, semester berbeda)
        $course1 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF301'],
            [
                'nama_mk' => 'Pengembangan Aplikasi Website',
                'deskripsi' => 'Mata kuliah yang membahas arsitektur aplikasi web modern menggunakan framework Laravel, MVC, Eloquent ORM, dan keamanan web.',
                'sks' => 4,
                'semester' => 'Ganjil 2026/2027',
                'dosen_id' => $dosen->id,
            ]
        );

        $course2 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF204'],
            [
                'nama_mk' => 'Basis Data Relasional',
                'deskripsi' => 'Mata kuliah yang membahas basis data relasional, normalisasi, ERD, dan optimasi query SQL.',
                'sks' => 3,
                'semester' => 'Genap 2026/2027',
                'dosen_id' => $dosen->id,
            ]
        );

        $course3 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF402'],
            [
                'nama_mk' => 'Arsitektur Layanan Awan',
                'deskripsi' => 'Mata kuliah yang membahas konsep cloud computing, layanan IaaS, PaaS, SaaS, dan deployment aplikasi.',
                'sks' => 2,
                'semester' => 'Ganjil 2025/2026',
                'dosen_id' => $dosen->id,
            ]
        );

        // 4. Daftarkan Mahasiswa ke Mata Kuliah (status approved)
        $course1->students()->syncWithoutDetaching([
            $mahasiswa1->id => ['status' => 'approved'],
            $mahasiswa2->id => ['status' => 'approved'],
        ]);
        $course2->students()->syncWithoutDetaching([
            $mahasiswa1->id => ['status' => 'approved'],
        ]);
        $course3->students()->syncWithoutDetaching([
            $mahasiswa2->id => ['status' => 'approved'],
        ]);

        // 5. Tambahkan Materi Contoh
        Material::updateOrCreate(
            ['course_id' => $course1->id, 'minggu_ke' => 1, 'urutan' => 1],
            [
                'judul' => 'Pengenalan Arsitektur MVC & Routing di Laravel 12',
                'konten' => 'Mempelajari alur HTTP Request, Route Middleware, Controller, dan pembuatan View menggunakan Blade Template.',
            ]
        );

        // 6. Tugas (2 tugas pada mata kuliah IF301)
        $tugas1 = Assignment::updateOrCreate(
            ['course_id' => $course1->id, 'judul' => 'Tugas 1: Implementasi Middleware & Eloquent ORM'],
            [
                'deskripsi' => 'Jelaskan cara kerja Route Middleware untuk proteksi peran pengguna serta implementasi relasi Eloquent pada studi kasus CeLOE LMS.',
                'deadline' => '2026-11-28 07:11:00',
            ]
        );

        $tugas2 = Assignment::updateOrCreate(
            ['course_id' => $course1->id, 'judul' => 'Tugas 2: Filter, Sorting, dan Ekspor CSV'],
            [
                'deskripsi' => 'Jelaskan langkah implementasi filter semester, sorting data, dan ekspor rekap nilai ke format CSV pada Laravel.',
                'deadline' => '2026-12-05 23:59:00',
            ]
        );

        // 7. Contoh Submission (nilai tinggi, nilai rendah, dan belum dinilai)
        Submission::updateOrCreate(
            ['assignment_id' => $tugas1->id, 'user_id' => $mahasiswa1->id],
            [
                'jawaban' => 'Middleware di Laravel berfungsi menyaring HTTP request sebelum mencapai controller. Pada CeLOE LMS, RoleMiddleware memeriksa apakah role pengguna sesuai dengan parameter route.',
                'submitted_at' => now()->subDays(2),
                'nilai' => 85,
                'catatan_dosen' => 'Penjelasan sangat terstruktur dan tepat sasaran. Pertahankan!',
            ]
        );

        Submission::updateOrCreate(
            ['assignment_id' => $tugas1->id, 'user_id' => $mahasiswa2->id],
            [
                'jawaban' => 'Middleware adalah filter untuk request. Eloquent dipakai untuk mengakses database.',
                'submitted_at' => now()->subDay(),
                'nilai' => 60,
                'catatan_dosen' => 'Jawaban masih terlalu singkat, tambahkan contoh kode dan alur request.',
            ]
        );

        Submission::updateOrCreate(
            ['assignment_id' => $tugas2->id, 'user_id' => $mahasiswa1->id],
            [
                'jawaban' => 'Filter semester memakai query where pada controller, sorting memakai orderBy, dan ekspor CSV memakai response()->stream().',
                'submitted_at' => now(),
                'nilai' => null,
                'catatan_dosen' => null,
            ]
        );
    }
}