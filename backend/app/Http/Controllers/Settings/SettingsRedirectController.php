<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SettingsRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return to_route('profile.edit');
    }
}
