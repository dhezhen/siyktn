<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman depan bukan lagi halaman sambutan bawaan Laravel. Seluruh
     * isinya berada di balik login, jadi tamu langsung diarahkan ke sana.
     */
    public function test_halaman_depan_meminta_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_tamu_diminta_login_sebelum_masuk_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
