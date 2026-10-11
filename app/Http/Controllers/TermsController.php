<?php

namespace App\Http\Controllers;

use App\Models\PortfolioSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermsController extends Controller
{
    /**
     * Display Platform Terms of Service, Privacy Policy & Portfolio Guidelines.
     */
    public function show(Request $request): Response
    {
        $termsContent = PortfolioSetting::get('terms_and_conditions', '');

        return Inertia::render('Terms', [
            'termsContent' => $termsContent,
        ]);
    }
}
