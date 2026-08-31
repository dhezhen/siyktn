<?php

namespace App\Http\Controllers;

use App\Models\Muhaffizh;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MuhaffizhController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:muhaffizh.view', only: ['index', 'show']),
            new Middleware('permission:muhaffizh.create', only: ['create', 'store']),
            new Middleware('permission:muhaffizh.update', only: ['edit', 'update']),
            new Middleware('permission:muhaffizh.delete', only: ['destroy']),
            new Middleware('permission:muhaffizh.export', only: ['export']),

            // Membuatkan akun berarti menambah pengguna baru, jadi izin modul
            // pengguna ikut dituntut — bukan hanya izin muhaffizh.
            new Middleware('permission:muhaffizh.update', only: ['buatkanAkun']),
            new Middleware('permission:user.create', only: ['buatkanAkun']),
        ];
    }

    public function index(Request $request): View
    {
        $muhaffizh = Muhaffizh::query()
            ->withCount(['halaqah' => fn ($q) => $q->aktif()])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('jenis_kelamin'), fn ($q) => $q->where('jenis_kelamin', $request->string('jenis_kelamin')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($sub) => $sub
                    ->where('nama', 'like', $term)
                    ->orWhere('kode', 'like', $term)
                    ->orWhere('no_hp', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('muhaffizh.index', ['muhaffizh' => $muhaffizh]);
    }

    public function show(Muhaffizh $muhaffizh): View
    {
        $muhaffizh->load('user.roles:id,name');

        return view('muhaffizh.show', [
            'muhaffizh' => $muhaffizh,
            'halaqah' => $muhaffizh->halaqah()
                ->with('angkatan:id,nama,kode')
                ->withCount('anggotaAktif')
                ->orderByDesc('is_aktif')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('muhaffizh.form', [
            'muhaffizh' => new Muhaffizh([
                'kode' => Muhaffizh::kodeBerikutnya(),
                'status' => 'aktif',
                'tanggal_bergabung' => now()->toDateString(),
            ]),
            'akunTersedia' => $this->akunTersedia(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($foto = $this->simpanFoto($request)) {
            $data['foto'] = $foto;
        }

        $muhaffizh = Muhaffizh::create($data);

        return redirect()->route('muhaffizh.show', $muhaffizh)
            ->with('success', 'Muhaffizh '.$muhaffizh->nama.' berhasil ditambahkan.');
    }

    public function edit(Muhaffizh $muhaffizh): View
    {
        return view('muhaffizh.form', [
            'muhaffizh' => $muhaffizh,
            'akunTersedia' => $this->akunTersedia($muhaffizh),
        ]);
    }

    public function update(Request $request, Muhaffizh $muhaffizh): RedirectResponse
    {
        $data = $this->validated($request, $muhaffizh);

        if ($foto = $this->simpanFoto($request)) {
            $this->hapusFoto($muhaffizh);
            $data['foto'] = $foto;
        }

        $muhaffizh->update($data);

        return redirect()->route('muhaffizh.index')
            ->with('success', 'Data '.$muhaffizh->nama.' berhasil diperbarui.');
    }

    public function destroy(Muhaffizh $muhaffizh): RedirectResponse
    {
        // Halaqah yang pernah diampu adalah jejak pembimbingan, bukan data
        // sampah. Menonaktifkan lebih tepat daripada menghapus.
        if ($muhaffizh->halaqah()->exists()) {
            $jumlah = $muhaffizh->halaqah()->count();

            return back()->with('error', $muhaffizh->nama.' masih tercatat mengampu '.$jumlah.
                ' halaqah. Alihkan halaqahnya ke muhaffizh lain, atau ubah statusnya menjadi nonaktif.');
        }

        $nama = $muhaffizh->nama;
        $muhaffizh->delete();

        return redirect()->route('muhaffizh.index')->with('success', 'Muhaffizh '.$nama.' dihapus.');
    }

    public function export(): StreamedResponse
    {
        $namaBerkas = 'muhaffizh-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () {
            $keluaran = fopen('php://output', 'w');

            fputcsv($keluaran, [
                'Kode', 'Nama', 'Jenis Kelamin', 'No HP', 'Email',
                'Pendidikan', 'Sanad', 'Bergabung', 'Status',
            ]);

            Muhaffizh::orderBy('nama')->chunk(200, function ($baris) use ($keluaran) {
                foreach ($baris as $item) {
                    fputcsv($keluaran, [
                        $item->kode,
                        $item->nama,
                        $item->jenis_kelamin_label,
                        $item->no_hp,
                        $item->email,
                        $item->pendidikan,
                        $item->sanad_riwayat,
                        $item->tanggal_bergabung?->format('Y-m-d'),
                        $item->status,
                    ]);
                }
            });

            fclose($keluaran);
        }, $namaBerkas, ['Content-Type' => 'text/csv']);
    }

    /**
     * Buatkan akun login sekaligus untuk seorang muhaffizh.
     *
     * Sebelumnya petugas harus membuat pengguna di modul lain, mengingat
     * memberi role yang benar, lalu kembali ke sini untuk menautkannya.
     */
    public function buatkanAkun(Muhaffizh $muhaffizh): RedirectResponse
    {
        if ($muhaffizh->user_id) {
            return back()->with('error', $muhaffizh->nama.' sudah memiliki akun.');
        }

        if (! $muhaffizh->email) {
            return back()->with('error', 'Isi dulu email '.$muhaffizh->nama.
                ', karena email dipakai untuk masuk dan memulihkan kata sandi.');
        }

        if (User::withTrashed()->where('email', $muhaffizh->email)->exists()) {
            return back()->with('error', 'Email '.$muhaffizh->email.' sudah dipakai akun lain. '.
                'Hubungkan akun tersebut lewat kolom Akun Pengguna, atau ganti email muhaffizh ini.');
        }

        $sandi = Str::password(10, symbols: false);

        $user = DB::transaction(function () use ($muhaffizh, $sandi) {
            $user = User::create([
                'name' => $muhaffizh->nama,
                'username' => $this->usernameUnik($muhaffizh->nama),
                'email' => $muhaffizh->email,
                'phone' => $muhaffizh->no_hp,
                'password' => $sandi,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            // Role muhaffizh menempel sendiri lewat Muhaffizh::booted().
            $muhaffizh->update(['user_id' => $user->id]);

            return $user;
        });

        return back()->with('success', 'Akun untuk '.$muhaffizh->nama.' dibuat. Username: '.$user->username.
            ' · Kata sandi sementara: '.$sandi.' — catat sekarang, tidak ditampilkan lagi. '.
            'Kata sandi wajib diganti saat login pertama.');
    }

    /**
     * Username dari nama, mis. "Ustadz Fauzan Maulana" jadi "fauzan.maulana".
     * Gelar dibuang supaya usernamenya tidak seragam berawalan "ustadz".
     */
    protected function usernameUnik(string $nama): string
    {
        $tanpaGelar = str_ireplace(['ustadzah', 'ustadz', 'ust.', 'ust '], '', $nama);
        $dasar = Str::limit(trim(Str::slug($tanpaGelar, '.'), '.'), 40, '') ?: 'muhaffizh';

        $username = $dasar;
        $urut = 1;

        // Username unik termasuk terhadap akun yang sudah dihapus, karena
        // indeks uniknya tidak ikut memperhatikan soft delete.
        while (User::withTrashed()->where('username', $username)->exists()) {
            $username = $dasar.(++$urut);
        }

        return $username;
    }

    /**
     * Akun yang boleh ditautkan ke seorang muhaffizh.
     *
     * Disaring dua kali: belum dipakai muhaffizh lain (satu akun hanya mewakili
     * satu muhaffizh, dijaga juga oleh indeks unik), dan belum memikul peran
     * lain — jangan sampai akun admin atau super admin ikut tertawarkan di sini.
     *
     * @return Collection<int, User>
     */
    protected function akunTersedia(?Muhaffizh $muhaffizh = null): Collection
    {
        $terpakai = Muhaffizh::query()
            ->whereNotNull('user_id')
            ->when($muhaffizh, fn ($q) => $q->where('id', '!=', $muhaffizh->id))
            ->pluck('user_id');

        return User::query()
            ->whereNotIn('id', $terpakai)
            ->where(function ($q) use ($muhaffizh) {
                $q->where(fn ($sub) => $sub
                    ->where('is_active', true)
                    ->whereDoesntHave('roles', fn ($r) => $r->where('name', '!=', Muhaffizh::ROLE)));

                // Akun yang sedang tertaut tetap ditampilkan apa adanya, supaya
                // menyimpan form tidak diam-diam memutus tautannya.
                if ($muhaffizh?->user_id) {
                    $q->orWhere('id', $muhaffizh->user_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'username']);
    }

    protected function simpanFoto(Request $request): ?string
    {
        return $request->hasFile('foto')
            ? $request->file('foto')->store('muhaffizh', 'public')
            : null;
    }

    protected function hapusFoto(Muhaffizh $muhaffizh): void
    {
        if ($muhaffizh->foto) {
            Storage::disk('public')->delete($muhaffizh->foto);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Muhaffizh $muhaffizh = null): array
    {
        $data = $request->validate([
            'kode' => [
                'required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/',
                Rule::unique('muhaffizh', 'kode')->ignore($muhaffizh?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'no_hp' => ['nullable', 'string', 'max:25'],
            'email' => ['nullable', 'email', 'max:150'],
            'pendidikan' => ['nullable', 'string', 'max:150'],
            'sanad_riwayat' => ['nullable', 'string', 'max:150'],
            'tanggal_bergabung' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'user_id' => [
                'nullable', 'exists:users,id',
                Rule::unique('muhaffizh', 'user_id')->ignore($muhaffizh?->id),
            ],
            'foto' => ['nullable', 'image', 'max:2048'],
        ], [
            'kode.regex' => 'Kode hanya boleh huruf, angka, dan tanda hubung. Contoh: MHF-001.',
            'user_id.unique' => 'Akun ini sudah terhubung ke muhaffizh lain.',
        ], [
            'kode' => 'kode',
            'nama' => 'nama',
            'jenis_kelamin' => 'jenis kelamin',
            'no_hp' => 'nomor HP',
            'email' => 'email',
            'pendidikan' => 'pendidikan',
            'sanad_riwayat' => 'sanad/riwayat',
            'tanggal_bergabung' => 'tanggal bergabung',
            'status' => 'status',
            'keterangan' => 'keterangan',
            'user_id' => 'akun pengguna',
            'foto' => 'foto',
        ]);

        unset($data['foto']);

        return $data;
    }
}
