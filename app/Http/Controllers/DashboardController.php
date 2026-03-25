<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'appName' => Config::get('app.name', 'KINAYA POS API'),
            'appEnv'  => Config::get('app.env'),
            'apiBase' => url('/api'),
        ]);
    }

    public function admin()
    {
        return view('admin', [
            'appName' => Config::get('app.name', 'KINAYA POS'),
            'apiBase' => url('/api'),
        ]);
    }
}
