@extends('layouts.app')

@section('title', 'Daftar Pengumpulan — ' . $assignment->judul)

@section('content')
<div style="margin-bottom: 1.25rem;">
    <a href="{{ route('courses.show', $assignment->course_id) }}" class="btn btn-secondary btn-sm">
        Kembali ke Mata Kuliah
    </a>
</div>

@php
    $gradedSubmissions = $submissions->whereNotNull('nilai');
    $averageScore = $gradedSubmissions->count() > 0 ? round($gradedSubmissions->avg('nilai'), 1) : null;
    $totalStudents = $students->count();
    $totalSubmitted = $students->filter(fn($s) => $submissions->has($s->id))->count();
@endphp

<!-- Header Penugasan -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span class="code-pill">{{ $assignment->course->kode_mk }}</span>
                <span style="font-size: 0.85rem; color: var(--text-muted);">{{ $assignment->course->nama_mk }}</span>
            </div>
            <h1 class="card-title">{{ $assignment->judul }}</h1>
            <p class="card-subtitle">
                Batas Pengumpulan: <strong>{{ $assignment->deadline ? $assignment->deadline->translatedFormat('l, d F Y — H:i') . ' WIB' : '-' }}</strong>
                @if($assignment->isOverdue())
                    <span style="color: #dc2626; font-size: 0.8125rem; font-weight: 600; margin-left: 0.5rem;">Batas Waktu Terlewat</span>
                @else
                    <span style="color: #059669; font-size: 0.8125rem; font-weight: 600; margin-left: 0.5rem;">Sedang Berlangsung</span>
                @endif
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">

            {{-- Tombol Export CSV --}}
            <a href="{{ route('assignments.export-csv', $assignment) }}" class="btn btn-primary btn-sm">
                Export CSV
            </a>

            <a href="{{ route('assignments.edit', $assignment->id) }}" class="btn btn-secondary btn-sm">
                Edit Tugas
            </a>
        </div>
    </div>

    <div class="card-body" style="background-color: #f8fafc; border-top: 1px solid var(--border-color); padding: 1rem 1.25rem;">