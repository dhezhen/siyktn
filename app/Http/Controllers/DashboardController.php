<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'stats' => $this->stats(),
            'activities' => $this->recentActivities(),
        ]);
    }

    /**
     * Kartu ringkasan. Hanya menampilkan angka yang boleh dilihat pengguna.
     *
     * @return array<int, array{label: string, value: int|string, icon: string}>
     */
    protected function stats(): array
    {
        $user = Auth::user();
        $stats = [];

        if ($user->can('user.view')) {
            $stats[] = ['label' => 'Total Pengguna', 'value' => User::count(), 'icon' => 'users'];
            $stats[] = ['label' => 'Pengguna Aktif', 'value' => User::active()->count(), 'icon' => 'check-circle'];
        }

        if ($user->can('role.view')) {
            $stats[] = ['label' => 'Role', 'value' => Role::count(), 'icon' => 'shield'];
        }

        if ($user->can('menu.view')) {
            $stats[] = ['label' => 'Menu Aktif', 'value' => Menu::active()->count(), 'icon' => 'list'];
        }

        return $stats;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Activity>
     */
    protected function recentActivities()
    {
        if (! Auth::user()->can('activity.view')) {
            return collect();
        }

        return Activity::with('causer:id,name')->latest()->limit(10)->get();
    }
}
