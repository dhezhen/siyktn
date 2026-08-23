<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MenuController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:menu.view')];
    }

    public function index(): View
    {
        return view('menu.index');
    }
}
