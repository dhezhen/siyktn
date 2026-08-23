<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:setting.view', only: ['edit']),
            new Middleware('permission:setting.update', only: ['update']),
        ];
    }

    public function edit(): View
    {
        return view('setting.edit', [
            'groups' => Setting::orderBy('group')->orderBy('id')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = Setting::all()->keyBy('key');

        $rules = [];

        foreach ($settings as $key => $setting) {
            $rules["values.{$key}"] = match ($setting->type) {
                'image' => ['nullable', 'image', 'max:1024'],
                'boolean' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:1000'],
            };
        }

        // Nama aplikasi selalu wajib — dipakai di judul halaman dan sidebar.
        $rules['values.app_name'] = ['required', 'string', 'max:100'];

        $request->validate($rules, [
            'values.app_name.required' => 'Nama aplikasi wajib diisi.',
        ]);

        foreach ($settings as $key => $setting) {
            if ($setting->type === 'image') {
                if ($request->hasFile("values.{$key}")) {
                    if ($setting->value) {
                        Storage::disk('public')->delete($setting->value);
                    }

                    $setting->update(['value' => $request->file("values.{$key}")->store('settings', 'public')]);
                }

                continue;
            }

            $setting->update(['value' => $request->input("values.{$key}")]);
        }

        Setting::flush();

        return back()->with('success', 'Pengaturan aplikasi berhasil disimpan.');
    }
}
