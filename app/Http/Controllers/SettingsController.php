<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

final class SettingsController
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('settings.index');
    }
}
