@include('errors.layout', [
    'code' => 403,
    'title' => 'Anda tidak punya akses ke halaman ini',
    'message' => $exception?->getMessage() ?: 'Hubungi administrator bila Anda merasa seharusnya bisa membuka halaman ini.',
])
