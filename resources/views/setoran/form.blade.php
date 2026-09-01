@php
$editing = $setoran->exists;

$surahs = [
    ['no'=>1,'name'=>'Al-Faatihah','ayahs'=>7,'juz'=>1],
    ['no'=>2,'name'=>'Al-Baqarah','ayahs'=>286,'juz'=>1],
    ['no'=>3,'name'=>'Ali \'Imran','ayahs'=>200,'juz'=>3],
    ['no'=>4,'name'=>'An-Nisaa\'','ayahs'=>176,'juz'=>4],
    ['no'=>5,'name'=>'Al-Maa\'idah','ayahs'=>120,'juz'=>6],
    ['no'=>6,'name'=>'Al-An\'aam','ayahs'=>165,'juz'=>7],
    ['no'=>7,'name'=>'Al-A\'raaf','ayahs'=>206,'juz'=>8],
    ['no'=>8,'name'=>'Al-Anfaal','ayahs'=>75,'juz'=>9],
    ['no'=>9,'name'=>'At-Tawbah','ayahs'=>129,'juz'=>10],
    ['no'=>10,'name'=>'Yunus','ayahs'=>109,'juz'=>11],
    ['no'=>11,'name'=>'Hud','ayahs'=>123,'juz'=>11],
    ['no'=>12,'name'=>'Yusuf','ayahs'=>111,'juz'=>12],
    ['no'=>13,'name'=>'Ar-Ra\'d','ayahs'=>43,'juz'=>13],
    ['no'=>14,'name'=>'Ibrahim','ayahs'=>52,'juz'=>13],
    ['no'=>15,'name'=>'Al-Hijr','ayahs'=>99,'juz'=>14],
    ['no'=>16,'name'=>'An-Nahl','ayahs'=>128,'juz'=>14],
    ['no'=>17,'name'=>'Al-Israa\'','ayahs'=>111,'juz'=>15],
    ['no'=>18,'name'=>'Al-Kahfi','ayahs'=>110,'juz'=>15],
    ['no'=>19,'name'=>'Maryam','ayahs'=>98,'juz'=>16],
    ['no'=>20,'name'=>'Thaa-Haa','ayahs'=>135,'juz'=>16],
    ['no'=>21,'name'=>'Al-Anbiyaa\'','ayahs'=>112,'juz'=>17],
    ['no'=>22,'name'=>'Al-Hajj','ayahs'=>78,'juz'=>17],
    ['no'=>23,'name'=>'Al-Mu\'minun','ayahs'=>118,'juz'=>18],
    ['no'=>24,'name'=>'An-Nuur','ayahs'=>64,'juz'=>18],
    ['no'=>25,'name'=>'Al-Furqaan','ayahs'=>77,'juz'=>18],
    ['no'=>26,'name'=>'Asy-Syu\'araa\'','ayahs'=>227,'juz'=>19],
    ['no'=>27,'name'=>'An-Naml','ayahs'=>93,'juz'=>19],
    ['no'=>28,'name'=>'Al-Qashash','ayahs'=>88,'juz'=>20],
    ['no'=>29,'name'=>'Al-\'Ankabut','ayahs'=>69,'juz'=>20],
    ['no'=>30,'name'=>'Ar-Ruum','ayahs'=>60,'juz'=>21],
    ['no'=>31,'name'=>'Luqman','ayahs'=>34,'juz'=>21],
    ['no'=>32,'name'=>'As-Sajdah','ayahs'=>30,'juz'=>21],
    ['no'=>33,'name'=>'Al-Ahzaab','ayahs'=>73,'juz'=>21],
    ['no'=>34,'name'=>'Saba\'','ayahs'=>54,'juz'=>22],
    ['no'=>35,'name'=>'Faathir','ayahs'=>45,'juz'=>22],
    ['no'=>36,'name'=>'Yaa Siin','ayahs'=>83,'juz'=>22],
    ['no'=>37,'name'=>'Ash-Shaaffaat','ayahs'=>182,'juz'=>23],
    ['no'=>38,'name'=>'Shaad','ayahs'=>88,'juz'=>23],
    ['no'=>39,'name'=>'Az-Zumar','ayahs'=>75,'juz'=>23],
    ['no'=>40,'name'=>'Ghaafir','ayahs'=>85,'juz'=>24],
    ['no'=>41,'name'=>'Fushshilat','ayahs'=>54,'juz'=>24],
    ['no'=>42,'name'=>'Asy-Syuura','ayahs'=>53,'juz'=>25],
    ['no'=>43,'name'=>'Az-Zukhruf','ayahs'=>89,'juz'=>25],
    ['no'=>44,'name'=>'Ad-Dukhaan','ayahs'=>59,'juz'=>25],
    ['no'=>45,'name'=>'Al-Jaatsiyah','ayahs'=>37,'juz'=>25],
    ['no'=>46,'name'=>'Al-Ahqaaf','ayahs'=>35,'juz'=>26],
    ['no'=>47,'name'=>'Muhammad','ayahs'=>38,'juz'=>26],
    ['no'=>48,'name'=>'Al-Fath','ayahs'=>29,'juz'=>26],
    ['no'=>49,'name'=>'Al-Hujuraat','ayahs'=>18,'juz'=>26],
    ['no'=>50,'name'=>'Qaaf','ayahs'=>45,'juz'=>26],
    ['no'=>51,'name'=>'Adz-Dzaariyaat','ayahs'=>60,'juz'=>26],
    ['no'=>52,'name'=>'Ath-Thuur','ayahs'=>49,'juz'=>27],
    ['no'=>53,'name'=>'An-Najm','ayahs'=>62,'juz'=>27],
    ['no'=>54,'name'=>'Al-Qamar','ayahs'=>55,'juz'=>27],
    ['no'=>55,'name'=>'Ar-Rahmaan','ayahs'=>78,'juz'=>27],
    ['no'=>56,'name'=>'Al-Waaqi\'ah','ayahs'=>96,'juz'=>27],
    ['no'=>57,'name'=>'Al-Hadiid','ayahs'=>29,'juz'=>27],
    ['no'=>58,'name'=>'Al-Mujaadilah','ayahs'=>22,'juz'=>28],
    ['no'=>59,'name'=>'Al-Hasyr','ayahs'=>24,'juz'=>28],
    ['no'=>60,'name'=>'Al-Mumtahanah','ayahs'=>13,'juz'=>28],
    ['no'=>61,'name'=>'Ash-Shaff','ayahs'=>14,'juz'=>28],
    ['no'=>62,'name'=>'Al-Jumu\'ah','ayahs'=>11,'juz'=>28],
    ['no'=>63,'name'=>'Al-Munaafiquun','ayahs'=>11,'juz'=>28],
    ['no'=>64,'name'=>'At-Taghaabun','ayahs'=>18,'juz'=>28],
    ['no'=>65,'name'=>'Ath-Thalaaq','ayahs'=>12,'juz'=>28],
    ['no'=>66,'name'=>'At-Tahriim','ayahs'=>12,'juz'=>28],
    ['no'=>67,'name'=>'Al-Mulk','ayahs'=>30,'juz'=>29],
    ['no'=>68,'name'=>'Al-Qalam','ayahs'=>52,'juz'=>29],
    ['no'=>69,'name'=>'Al-Haaqqah','ayahs'=>52,'juz'=>29],
    ['no'=>70,'name'=>'Al-Ma\'aarij','ayahs'=>44,'juz'=>29],
    ['no'=>71,'name'=>'Nuh','ayahs'=>28,'juz'=>29],
    ['no'=>72,'name'=>'Al-Jinn','ayahs'=>28,'juz'=>29],
    ['no'=>73,'name'=>'Al-Muzzammil','ayahs'=>20,'juz'=>29],
    ['no'=>74,'name'=>'Al-Muddatsir','ayahs'=>56,'juz'=>29],
    ['no'=>75,'name'=>'Al-Qiyaamah','ayahs'=>40,'juz'=>29],
    ['no'=>76,'name'=>'Al-Insaan','ayahs'=>31,'juz'=>29],
    ['no'=>77,'name'=>'Al-Mursalaat','ayahs'=>50,'juz'=>29],
    ['no'=>78,'name'=>'An-Naba\'','ayahs'=>40,'juz'=>30],
    ['no'=>79,'name'=>'An-Naazi\'aat','ayahs'=>46,'juz'=>30],
    ['no'=>80,'name'=>'\'Abasa','ayahs'=>42,'juz'=>30],
    ['no'=>81,'name'=>'At-Takwiir','ayahs'=>29,'juz'=>30],
    ['no'=>82,'name'=>'Al-Infithaar','ayahs'=>19,'juz'=>30],
    ['no'=>83,'name'=>'Al-Muthaffifiin','ayahs'=>36,'juz'=>30],
    ['no'=>84,'name'=>'Al-Insyiqaaq','ayahs'=>25,'juz'=>30],
    ['no'=>85,'name'=>'Al-Buruuj','ayahs'=>22,'juz'=>30],
    ['no'=>86,'name'=>'Ath-Thaariq','ayahs'=>17,'juz'=>30],
    ['no'=>87,'name'=>'Al-A\'laa','ayahs'=>19,'juz'=>30],
    ['no'=>88,'name'=>'Al-Ghaasyiyah','ayahs'=>26,'juz'=>30],
    ['no'=>89,'name'=>'Al-Fajr','ayahs'=>30,'juz'=>30],
    ['no'=>90,'name'=>'Al-Balad','ayahs'=>20,'juz'=>30],
    ['no'=>91,'name'=>'Asy-Syams','ayahs'=>15,'juz'=>30],
    ['no'=>92,'name'=>'Al-Lail','ayahs'=>21,'juz'=>30],
    ['no'=>93,'name'=>'Adh-Dhuhaa','ayahs'=>11,'juz'=>30],
    ['no'=>94,'name'=>'Al-Insyirah','ayahs'=>8,'juz'=>30],
    ['no'=>95,'name'=>'At-Tiin','ayahs'=>8,'juz'=>30],
    ['no'=>96,'name'=>'Al-\'Alaq','ayahs'=>19,'juz'=>30],
    ['no'=>97,'name'=>'Al-Qadr','ayahs'=>5,'juz'=>30],
    ['no'=>98,'name'=>'Al-Bayyinah','ayahs'=>8,'juz'=>30],
    ['no'=>99,'name'=>'Az-Zalzalah','ayahs'=>8,'juz'=>30],
    ['no'=>100,'name'=>'Al-\'Aadiyaat','ayahs'=>11,'juz'=>30],
    ['no'=>101,'name'=>'Al-Qaari\'ah','ayahs'=>11,'juz'=>30],
    ['no'=>102,'name'=>'At-Takaatsur','ayahs'=>8,'juz'=>30],
    ['no'=>103,'name'=>'Al-\'Ashr','ayahs'=>3,'juz'=>30],
    ['no'=>104,'name'=>'Al-Humazah','ayahs'=>9,'juz'=>30],
    ['no'=>105,'name'=>'Al-Fiil','ayahs'=>5,'juz'=>30],
    ['no'=>106,'name'=>'Quraisy','ayahs'=>4,'juz'=>30],
    ['no'=>107,'name'=>'Al-Maa\'uun','ayahs'=>7,'juz'=>30],
    ['no'=>108,'name'=>'Al-Kautsar','ayahs'=>3,'juz'=>30],
    ['no'=>109,'name'=>'Al-Kaafiruun','ayahs'=>6,'juz'=>30],
    ['no'=>110,'name'=>'An-Nashr','ayahs'=>3,'juz'=>30],
    ['no'=>111,'name'=>'Al-Lahab','ayahs'=>5,'juz'=>30],
    ['no'=>112,'name'=>'Al-Ikhlaash','ayahs'=>4,'juz'=>30],
    ['no'=>113,'name'=>'Al-Falaq','ayahs'=>5,'juz'=>30],
    ['no'=>114,'name'=>'An-Naas','ayahs'=>6,'juz'=>30]
];
@endphp

<x-layouts::app :title="$editing ? 'Ubah Setoran' : 'Catat Setoran'">
    <x-page-header :title="$editing ? 'Ubah Setoran' : 'Catat Setoran'"
                   :subtitle="$halaqah->nama.' · '.$halaqah->kode.' · '.($halaqah->angkatan?->nama ?? '')">
        <x-slot:actions>
            <x-button :href="route('halaqah.show', $halaqah)" variant="secondary">Kembali ke Halaqah</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($anggota->isEmpty())
        <x-card>
            <x-empty-state icon="users" title="Belum ada santri di halaqah ini"
                           message="Tempatkan santri terlebih dahulu sebelum mencatat setoran." />
        </x-card>
    @else
        <form method="POST" action="{{ $editing ? route('setoran.update', $setoran) : route('setoran.store') }}"
              x-data="setoranForm()">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <x-card title="Setoran">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-select name="anggota_halaqah_id" label="Santri" required>
                                @foreach ($anggota as $item)
                                    <option value="{{ $item->id }}"
                                        @selected(old('anggota_halaqah_id', $anggotaTerpilih) == $item->id)>
                                        {{ $item->pendaftaran?->peserta?->nama ?? '—' }}
                                        ({{ $item->pendaftaran?->nomor_induk ?: 'tanpa nomor' }})
                                    </option>
                                @endforeach
                            </x-select>

                            <x-input name="tanggal" type="datetime-local" label="Waktu Setoran" required
                                     :max="now()->format('Y-m-d\TH:i')"
                                     :value="old('tanggal', $setoran->tanggal?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'))" />

                            <x-select name="jenis" label="Jenis" required
                                      hint="Ziyadah menambah hafalan baru, muraja'ah mengulang yang lama.">
                                @foreach (['ziyadah' => 'Ziyadah (hafalan baru)', 'murajaah' => "Muraja'ah (mengulang)"] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('jenis', $setoran->jenis) === $value)>{{ $label }}</option>
                                @endforeach
                            </x-select>

                            <x-input name="jumlah_halaman" type="number" label="Jumlah Halaman" required
                                     step="0.5" min="0.5" max="100"
                                     :value="old('jumlah_halaman', $setoran->jumlah_halaman ? rtrim(rtrim((string) $setoran->jumlah_halaman, '0'), '.') : '1')"
                                     hint="Boleh setengah halaman, mis. 1,5 — ditulis 1.5." />
                        </div>
                    </x-card>

                    <x-card title="Bacaan" subtitle="Opsional, tetapi sangat membantu saat muraja'ah.">
                        <div class="grid gap-4 sm:grid-cols-4">
                            <div class="sm:col-span-3">
                                <x-select name="surah" label="Surah" x-model="selectedSurah" x-on:change="updateSurah()">
                                    <option value="">— Pilih Surah —</option>
                                    @foreach ($surahs as $s)
                                        <option value="{{ $s['no'] }} - {{ $s['name'] }}">
                                            {{ $s['no'] }} - {{ $s['name'] }}
                                        </option>
                                    @endforeach
                                </x-select>
                            </div>

                            <x-select name="juz" label="Juz" required x-model="juz">
                                <option value="">—</option>
                                <template x-for="i in 30" :key="i">
                                    <option :value="i" x-text="i"></option>
                                </template>
                            </x-select>

                            <div class="sm:col-span-2">
                                <x-select name="ayat_dari" label="Ayat Awal" x-model="ayatDari" required>
                                    <option value="">—</option>
                                    <template x-for="i in maxAyah" :key="i">
                                        <option :value="i" x-text="i"></option>
                                    </template>
                                </x-select>
                            </div>

                            <div class="sm:col-span-2">
                                <x-select name="ayat_sampai" label="Ayat Akhir" x-model="ayatSampai" required>
                                    <option value="">—</option>
                                    <template x-for="i in maxAyah" :key="i">
                                        <option :value="i" x-text="i"></option>
                                    </template>
                                </x-select>
                            </div>
                        </div>
                    </x-card>
                </div>

                <div class="space-y-4">
                    <x-card title="Penilaian">
                        <x-select name="kualitas" label="Kualitas" required>
                            @foreach ([
                                'mumtaz' => 'Mumtaz (istimewa)',
                                'jayyid_jiddan' => 'Jayyid Jiddan (sangat baik)',
                                'jayyid' => 'Jayyid (baik)',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('kualitas', $setoran->kualitas) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>

                        <div class="mt-4">
                            <label for="catatan" class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                            <textarea name="catatan" id="catatan" rows="4"
                                      placeholder="mis. perbaiki mad pada ayat 5"
                                      class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('catatan', $setoran->catatan) }}</textarea>
                            @error('catatan')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </x-card>

                    <x-card title="Penyimak">
                        <p class="text-sm text-slate-800">
                            {{ $halaqah->muhaffizh?->nama ?? 'Belum ada pengampu' }}
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">
                            @if ($editing)
                                Penyimak dibekukan pada saat setoran dicatat dan tidak ikut berubah
                                bila pengampu halaqah diganti.
                            @else
                                Diambil dari pengampu halaqah ini, lalu disimpan permanen di catatan
                                setoran. Nama Anda tercatat terpisah sebagai pengentri.
                            @endif
                        </p>
                    </x-card>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <x-button :href="route('halaqah.show', $halaqah)" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">{{ $editing ? 'Simpan Perubahan' : 'Catat Setoran' }}</x-button>
            </div>
        </form>
    @endif

    <script>
        function setoranForm() {
            return {
                surahs: @json($surahs),
                selectedSurah: '{{ old('surah', $setoran->surah) }}',
                juz: '{{ old('juz', $setoran->juz) }}',
                ayatDari: '{{ old('ayat_dari', $setoran->ayat_dari) }}',
                ayatSampai: '{{ old('ayat_sampai', $setoran->ayat_sampai) }}',
                
                get maxAyah() {
                    if (!this.selectedSurah) return 286;
                    let found = this.surahs.find(s => s.no + ' - ' + s.name === this.selectedSurah);
                    return found ? found.ayahs : 286;
                },
                
                updateSurah() {
                    let found = this.surahs.find(s => s.no + ' - ' + s.name === this.selectedSurah);
                    if (found) {
                        this.juz = found.juz;
                        // Reset ayat if they are out of bounds or not set
                        if (!this.ayatDari || this.ayatDari > found.ayahs) this.ayatDari = 1;
                        if (!this.ayatSampai || this.ayatSampai > found.ayahs) this.ayatSampai = found.ayahs;
                    }
                }
            }
        }
    </script>
</x-layouts::app>
