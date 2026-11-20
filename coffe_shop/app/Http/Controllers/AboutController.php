<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        return view('catalog.about', [
            'employees' => DB::table('users')
                ->whereIn('role', ['admin', 'kasir'])
                ->orderBy('name')
                ->limit(3)
                ->get(['name', 'role']),
        ]);
    }
}
