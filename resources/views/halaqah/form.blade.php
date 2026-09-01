@php($editing = $halaqah->exists)

<x-layouts::app :title="$editing ? 'Ubah Halaqah' : 'Tambah Halaqah'">
    <x-page-header :title="$editing ? 'Ubah Halaqah: '.$halaqah->nama : 'Tambah Halaqah'"
                   subtitle="Kelompok binaan dalam satu angkatan. Santrinya ditempatkan dari halaman detail halaqah." />

    @if ($daftarAngkatan->isEmpty())
        <x-card>
            <x-empty-state title="Belum ada angkatan yang terbuka"
                           message="Halaqah harus berada di dalam sebuah angkatan berstatus persiapan atau berjalan.">
                <x-slot:actions>
                    @can('angkatan.create')
                        <x-button :href="route('angkatan.create')">Buat Angkatan</x-button>
                    @endcan
                </x-slot:actions>
            </x-empty-state>
        </x-card>
    @else
        <form method="POST" action="{{ $editing ? route('halaqah.update', $halaqah) : route('halaqah.store') }}"
              x-data="{
                  kode: '{{ old('kode', $halaqah->kode) }}',
                  nama: '{{ old('nama', $halaqah->nama) }}',
                  jenis_kelamin: '{{ old('jenis_kelamin', $halaqah->jenis_kelamin ?? 'L') }}',
                  editing: {{ $editing ? 'true' : 'false' }}
              }">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <x-card title="Identitas Halaqah">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-select name="angkatan_id" label="Angkatan" required
                                      :hint="$editing ? 'Memindahkan angkatan akan memutus santri yang sudah ditempatkan.' : null">
                                @foreach ($daftarAngkatan as $item)
                                    <option value="{{ $item->id }}" @selected(old('angkatan_id', $halaqah->angkatan_id) == $item->id)>
                                        {{ $item->nama }} ({{ $item->kode }})
                                    </option>
                                @endforeach
                            </x-select>

                            <x-input name="kode" label="Kode" required x-model="kode"
                                     x-on:input="kode = kode.toUpperCase().replace(/\s+/g, '-').replace(/[^A-Z0-9-]/g, '').replace(/-+/g, '-'); $el.dataset.touched = true"
                                     placeholder="mis. H-01"
                                     hint="Kode otomatis disesuaikan (huruf besar, tanpa spasi)." />

                            <x-input name="nama" label="Nama Halaqah" required x-model="nama"
                                     x-on:input="if (!editing && !document.getElementById('kode').dataset.touched) kode = nama.toUpperCase().replace(/\s+/g, '-').replace(/[^A-Z0-9-]/g, '').replace(/-+/g, '-');"
                                     placeholder="mis. Halaqah Al-Fatih" />

                            <x-select name="jenis_kelamin" label="Peruntukan" required x-model="jenis_kelamin"
                                      x-on:change="
                                          if (!document.getElementById('kode').dataset.touched) {
                                              if (jenis_kelamin === 'L') {
                                                  kode = kode.replace(/^AKH-/, 'IKH-');
                                              } else {
                                                  kode = kode.replace(/^IKH-/, 'AKH-');
                                              }
                                          }
                                      "
                                      hint="Santri hanya bisa ditempatkan bila jenis kelaminnya cocok.">
                                <option value="L" @selected(old('jenis_kelamin', $halaqah->jenis_kelamin) === 'L')>Ikhwan (laki-laki)</option>
                                <option value="P" @selected(old('jenis_kelamin', $halaqah->jenis_kelamin) === 'P')>Akhwat (perempuan)</option>
                            </x-select>
                        </div>
                    </x-card>

                    <x-card title="Pelaksanaan">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-input name="ruang" label="Ruang" :value="old('ruang', $halaqah->ruang)"
                                     placeholder="mis. Masjid Lantai 2" />

                            <x-input name="jadwal" label="Jadwal" :value="old('jadwal', $halaqah->jadwal)"
                                     placeholder="mis. Ba'da Shubuh &amp; Ba'da Ashar" />
                        </div>

                        <div class="mt-4">
                            <label for="keterangan" class="mb-1 block text-sm font-medium text-slate-700">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" rows="3"
                                      class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('keterangan', $halaqah->keterangan) }}</textarea>
                            @error('keterangan')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </x-card>
                </div>

                <div class="space-y-4">
                    <x-card title="Muhaffizh Pengampu">
                        <x-select name="muhaffizh_id" label="Muhaffizh"
                                  x-on:change="
                                      if (!editing && $event.target.selectedIndex > 0) {
                                          let muhaffizhName = $event.target.options[$event.target.selectedIndex].dataset.nama;
                                          if (nama === '' || nama.startsWith('Halaqah ')) {
                                              nama = 'Halaqah ' + muhaffizhName;
                                              if (!document.getElementById('kode').dataset.touched) {
                                                  let prefix = jenis_kelamin === 'L' ? 'IKH-' : 'AKH-';
                                                  kode = prefix + muhaffizhName.toUpperCase().replace(/\s+/g, '-').replace(/[^A-Z0-9-]/g, '').replace(/-+/g, '-');
                                              }
                                          }
                                      }
                                  "
                                  hint="Boleh dikosongkan dulu bila pengampunya belum ditentukan.">
                            <option value="">— Belum ditugaskan —</option>
                            @foreach ($daftarMuhaffizh as $item)
                                <option value="{{ $item->id }}" data-nama="{{ $item->nama }}" @selected(old('muhaffizh_id', $halaqah->muhaffizh_id) == $item->id)>
                                    {{ $item->nama }} ({{ $item->kode }}) — {{ $item->jenis_kelamin === 'L' ? 'Ustadz (L)' : 'Ustadzah (P)' }}
                                </option>
                            @endforeach
                        </x-select>

                        <p class="mt-3 rounded-lg bg-sky-50 p-3 text-xs leading-relaxed text-sky-900">
                            Satu muhaffizh boleh mengampu lebih dari satu halaqah.
                        </p>
                    </x-card>

                    <x-card title="Kapasitas">
                        <x-input name="kuota" type="number" label="Kuota Santri" required min="0" max="999"
                                 :value="old('kuota', $halaqah->kuota ?? 0)"
                                 hint="Isi 0 bila tidak dibatasi." />

                        <label class="mt-4 flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_aktif" value="1"
                                   @checked(old('is_aktif', $halaqah->is_aktif ?? true))
                                   class="mt-0.5 size-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span>
                                Halaqah berjalan
                                <span class="block text-xs text-slate-500">
                                    Hilangkan centang bila halaqah ini sudah selesai. Santri yang sudah tercatat
                                    tetap tersimpan sebagai riwayat.
                                </span>
                            </span>
                        </label>
                    </x-card>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <x-button :href="route('halaqah.index')" variant="secondary">Batal</x-button>
                <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Halaqah' }}</x-button>
            </div>
        </form>
    @endif
</x-layouts::app>
