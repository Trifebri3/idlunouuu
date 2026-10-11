<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    /**
     * Store feedback, suggestions, or bug findings with humility and gratitude.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(['saran', 'temuan', 'masukan', 'lainnya'])],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
            'contact' => ['nullable', 'string', 'max:150'],
            'mood' => ['nullable', 'string', 'max:50'],
        ], [
            'category.required' => 'Silakan pilih jenis suara yang ingin disampaikan.',
            'message.required' => 'Ceritakan apa yang ada di pikiranmu agar kami bisa belajar.',
            'message.min' => 'Pesan terlalu singkat, ceritakan sedikit lebih lengkap ya.',
            'message.max' => 'Pesan maksimal 3000 karakter.',
        ]);

        Feedback::create([
            'user_id' => Auth::id(),
            'category' => $validated['category'],
            'message' => $validated['message'],
            'contact' => $validated['contact'] ?? null,
            'mood' => $validated['mood'] ?? null,
            'url_context' => $request->header('referer') ?: '/studio',
            'status' => 'unread',
        ]);

        return back()->with('success', 'Terima kasih banyak sudah menegur dan membimbing kami. Setiap kata darimu kami peluk erat untuk perbaikan LUNOU.');
    }
}
