<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Auth::user()->isMahasiswa()) {
            abort(403, 'Akses ditolak. Pengumpulan tugas hanya dapat dilakukan oleh Mahasiswa.');
        }

        $user = Auth::user();

        $enrollment = $assignment->course->students()->where('users.id', $user->id)->first();
        if (!$enrollment || $enrollment->pivot->status !== 'approved') {
            abort(403, 'Akses ditolak. Anda belum disetujui oleh Dosen untuk mengikuti mata kuliah tugas ini.');
        }

        if ($assignment->isOverdue()) {
            return back()->with('error', 'Gagal mengumpulkan: Batas waktu pengumpulan (deadline) tugas telah berakhir.');
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'string', 'min:10', 'max:20000'],
        ], [
            'jawaban.required' => 'Teks jawaban tugas wajib diisi.',
            'jawaban.min' => 'Jawaban terlalu pendek. Tuliskan jawaban minimal 10 karakter.',
            'jawaban.max' => 'Jawaban melebihi batas maksimal 20.000 karakter.',
        ]);

        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'jawaban' => $validated['jawaban'],
                'submitted_at' => now(),
            ]
        );

        return back()->with('success', 'Jawaban tugas Anda berhasil disimpan ke sistem.');
    }

    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        // 1. Pastikan hanya dosen pengampu mata kuliah yang boleh menilai
        if ($submission->assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // 2. Validasi input nilai dan catatan dosen
        $validated = $request->validate([
            'nilai' => ['required', 'integer', 'min:0', 'max:100'],
            'catatan_dosen' => ['nullable', 'string', 'max:1000'],
        ], [
            'nilai.required' => 'Nilai wajib diisi.',
            'nilai.integer' => 'Nilai harus berupa bilangan bulat.',
            'nilai.min' => 'Nilai minimal adalah 0.',
            'nilai.max' => 'Nilai maksimal adalah 100.',
            'catatan_dosen.max' => 'Catatan dosen maksimal 1000 karakter.',
        ]);

        // 3. Simpan perubahan
        $submission->update($validated);

        return back()->with('success', "Nilai untuk {$submission->user->name} berhasil disimpan.");
    }

    public function exportCsv(Assignment $assignment): StreamedResponse
    {
        // 1. Pastikan hanya dosen pengampu yang boleh mengunduh rekap
        if ($assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // 2. Muat relasi yang dibutuhkan
        $assignment->load(['course.approvedStudents', 'submissions']);

        $students = $assignment->course->approvedStudents->sortBy('name');
        $submissions = $assignment->submissions->keyBy('user_id');

        $filename = 'rekap-nilai-' . Str::slug($assignment->judul) . '.csv';

        // 3. Tulis CSV
        return response()->streamDownload(function () use ($students, $submissions) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 supaya karakter tampil benar di Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'No', 'Nama', 'NIM', 'Status Pengumpulan',
                'Waktu Pengumpulan', 'Nilai', 'Status Nilai', 'Catatan Dosen',
            ], ';');

            $no = 1;
            foreach ($students as $student) {
                $submission = $submissions->get($student->id);

                fputcsv($out, [
                    $no++,
                    $student->name,
                    $student->nomor_induk,
                    $submission ? 'Sudah Mengumpulkan' : 'Belum Mengumpulkan',
                    $submission?->submitted_at?->format('d-m-Y H:i') ?? '-',
                    $submission?->nilai ?? '-',
                    $submission ? $submission->status_nilai : 'Belum Dinilai',
                    $submission?->catatan_dosen ?? '-',
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}