<?php

/*
|--------------------------------------------------------------------------
| Pesan Validasi
|--------------------------------------------------------------------------
|
| Nama kolom (:attribute) diisi dari argumen `attributes` pada masing-masing
| $request->validate(), sehingga pesannya berbunyi wajar, mis.
| "Kolom nama lengkap wajib diisi."
|
| Hanya aturan yang dipakai (atau berpeluang dipakai) di sistem ini yang
| diterjemahkan, supaya berkasnya tetap ringkas dan mudah dirawat.
|
*/

return [

    'accepted' => 'Kolom :attribute harus disetujui.',
    'active_url' => 'Kolom :attribute bukan URL yang sah.',
    'after' => 'Kolom :attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berisi tanggal :date atau sesudahnya.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'array' => 'Kolom :attribute harus berupa daftar.',
    'before' => 'Kolom :attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berisi tanggal :date atau sebelumnya.',
    'boolean' => 'Kolom :attribute harus bernilai ya atau tidak.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi yang Anda masukkan salah.',
    'date' => 'Kolom :attribute bukan tanggal yang sah.',
    'date_equals' => 'Kolom :attribute harus berisi tanggal :date.',
    'date_format' => 'Format kolom :attribute tidak sesuai (:format).',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits angka.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max angka.',
    'dimensions' => 'Ukuran gambar pada kolom :attribute tidak sesuai.',
    'distinct' => 'Kolom :attribute berisi nilai yang sama dua kali.',
    'email' => 'Kolom :attribute harus berupa alamat email yang sah.',
    'ends_with' => 'Kolom :attribute harus diakhiri dengan salah satu dari: :values.',
    'exists' => 'Pilihan pada kolom :attribute tidak tersedia.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute wajib diisi.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => 'Pilihan pada kolom :attribute tidak sah.',
    'in_array' => 'Kolom :attribute tidak ada di dalam :other.',
    'integer' => 'Kolom :attribute harus berupa angka bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang sah.',
    'json' => 'Kolom :attribute harus berupa JSON yang sah.',
    'lowercase' => 'Kolom :attribute harus ditulis dengan huruf kecil.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'mimes' => 'Kolom :attribute harus berupa berkas bertipe: :values.',
    'mimetypes' => 'Kolom :attribute harus berupa berkas bertipe: :values.',
    'min' => [
        'array' => 'Kolom :attribute minimal berisi :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute minimal :min.',
        'string' => 'Kolom :attribute minimal :min karakter.',
    ],
    'not_in' => 'Pilihan pada kolom :attribute tidak sah.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'present' => 'Kolom :attribute harus ada.',
    'regex' => 'Format kolom :attribute tidak sesuai.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_if' => 'Kolom :attribute wajib diisi bila :other bernilai :value.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai :values.',
    'required_with' => 'Kolom :attribute wajib diisi bila ada :values.',
    'required_without' => 'Kolom :attribute wajib diisi bila tidak ada :values.',
    'same' => 'Kolom :attribute dan :other harus sama.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus :size karakter.',
    ],
    'string' => 'Kolom :attribute harus berupa teks.',
    'unique' => ':attribute tersebut sudah dipakai.',
    'uploaded' => 'Berkas :attribute gagal diunggah. Periksa ukuran berkasnya.',
    'uppercase' => 'Kolom :attribute harus ditulis dengan huruf besar.',
    'url' => 'Format URL pada kolom :attribute tidak sah.',

    'password' => [
        'letters' => 'Kolom :attribute harus mengandung minimal satu huruf.',
        'mixed' => 'Kolom :attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers' => 'Kolom :attribute harus mengandung minimal satu angka.',
        'symbols' => 'Kolom :attribute harus mengandung minimal satu simbol.',
        'uncompromised' => 'Kata sandi ini pernah bocor di internet. Pilih kata sandi lain.',
    ],

    /*
     | Pesan khusus per kolom bisa ditaruh di sini. Untuk pesan yang hanya
     | berlaku di satu formulir, lebih baik ditulis langsung di controller
     | agar konteksnya tetap terlihat.
     */
    'custom' => [],

    /*
     | Nama kolom global. Kebanyakan sudah diisi lewat argumen `attributes`
     | di masing-masing validate(); yang di sini jadi cadangan.
     */
    'attributes' => [
        'name' => 'nama',
        'nama' => 'nama',
        'username' => 'username',
        'email' => 'email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'phone' => 'nomor HP',
        'no_hp' => 'nomor HP',
        'alamat' => 'alamat',
        'tanggal_lahir' => 'tanggal lahir',
        'tempat_lahir' => 'tempat lahir',
        'jenis_kelamin' => 'jenis kelamin',
        'nik' => 'NIK',
        'angkatan_id' => 'angkatan',
        'status' => 'status',
        'kuota' => 'kuota',
        'tahun' => 'tahun',
        'kode' => 'kode',
    ],

];
