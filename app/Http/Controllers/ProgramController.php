<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:program.view', only: ['index']),
            new Middleware('permission:program.create', only: ['create', 'store']),
            new Middleware('permission:program.update', only: ['edit', 'update']),
            new Middleware('permission:program.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $query = Program::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'aktif') {
                $query->where('is_aktif', true);
            } elseif ($status === 'nonaktif') {
                $query->where('is_aktif', false);
            }
        }

        $programs = $query->latest()->paginate(10)->withQueryString();

        return view('program.index', [
            'programs' => $programs,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('program.form', [
            'program' => new Program([
                'kode' => Program::kodeBerikutnya(),
                'biaya_pendaftaran' => 100000,
                'is_aktif' => true,
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'string', 'max:20', 'unique:program,kode'],
            'durasi_hari' => ['required', 'integer', 'min:1', 'max:365'],
            'biaya_program' => ['required', 'numeric', 'min:0'],
            'biaya_pendaftaran' => ['required', 'numeric', 'min:0'],
            'is_aktif' => ['boolean'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_aktif'] = $request->has('is_aktif');

        Program::create($validated);

        return redirect()
            ->route('program.index')
            ->with('success', 'Paket program berhasil ditambahkan.');
    }

    public function edit(Program $program): View
    {
        return view('program.form', [
            'program' => $program,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['required', 'string', 'max:20', Rule::unique('program', 'kode')->ignore($program->id)],
            'durasi_hari' => ['required', 'integer', 'min:1', 'max:365'],
            'biaya_program' => ['required', 'numeric', 'min:0'],
            'biaya_pendaftaran' => ['required', 'numeric', 'min:0'],
            'is_aktif' => ['boolean'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['is_aktif'] = $request->has('is_aktif');

        $program->update($validated);

        return redirect()
            ->route('program.index')
            ->with('success', 'Paket program berhasil diperbarui.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $program->delete();

        return redirect()
            ->route('program.index')
            ->with('success', 'Paket program berhasil dihapus.');
    }
}
