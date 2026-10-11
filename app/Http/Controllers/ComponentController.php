<?php

namespace App\Http\Controllers;

use App\Models\ProfileComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComponentController extends Controller
{
    /**
     * Allowed component types by backend policy.
     */
    protected const ALLOWED_TYPES = [
        'social_link',
        'contact',
        'text',
        'heading',
        'featured_link',
        'video',
    ];

    /**
     * Store a newly created component in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->profile;

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(self::ALLOWED_TYPES)],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'string', 'max:1000'],
            'settings' => ['nullable', 'array'],
        ]);

        $maxOrder = $profile->components()->max('sort_order') ?? 0;

        $profile->components()->create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'url' => $validated['url'] ?? null,
            'settings' => $validated['settings'] ?? [],
            'sort_order' => $maxOrder + 1,
            'is_visible' => true,
        ]);

        return back()->with('success', 'Komponen berhasil ditambahkan.');
    }

    /**
     * Update the specified component.
     */
    public function update(Request $request, ProfileComponent $component): RedirectResponse
    {
        $this->authorizeOwnership($request, $component);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:1000'],
            'url' => ['nullable', 'string', 'max:1000'],
            'settings' => ['nullable', 'array'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $component->update($validated);

        return back()->with('success', 'Komponen berhasil diperbarui.');
    }

    /**
     * Remove the specified component from storage.
     */
    public function destroy(Request $request, ProfileComponent $component): RedirectResponse
    {
        $this->authorizeOwnership($request, $component);

        $component->delete();

        return back()->with('success', 'Komponen berhasil dihapus.');
    }

    /**
     * Reorder components list.
     */
    public function reorder(Request $request): JsonResponse|RedirectResponse
    {
        $profile = $request->user()->profile;

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:profile_components,id'],
        ]);

        foreach ($validated['order'] as $index => $id) {
            ProfileComponent::where('id', $id)
                ->where('profile_id', $profile->id)
                ->update(['sort_order' => $index + 1]);
        }

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['status' => 'success', 'message' => 'Urutan komponen disimpan']);
        }

        return back()->with('success', 'Urutan komponen disimpan.');
    }

    /**
     * Toggle visibility of a component.
     */
    public function toggleVisibility(Request $request, ProfileComponent $component): RedirectResponse
    {
        $this->authorizeOwnership($request, $component);

        $component->update([
            'is_visible' => ! $component->is_visible,
        ]);

        return back()->with('success', 'Visibilitas komponen diubah.');
    }

    /**
     * Ensure current user owns the profile component.
     */
    protected function authorizeOwnership(Request $request, ProfileComponent $component): void
    {
        if ($component->profile->user_id !== $request->user()->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }
    }
}
