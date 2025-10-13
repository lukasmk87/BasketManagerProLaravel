<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalPageController extends Controller
{
    /**
     * Show the specified legal page (Public).
     */
    public function show(string $localizedSlug)
    {
        // Map localized slugs to internal slugs
        $slugMap = [
            'datenschutz' => 'privacy',
            'agb' => 'terms',
            'impressum' => 'imprint',
            'gdpr' => 'gdpr',
        ];

        $internalSlug = $slugMap[$localizedSlug] ?? $localizedSlug;

        $page = LegalPage::where('slug', $internalSlug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('legal.show', [
            'page' => $page,
        ]);
    }

    /**
     * Display a listing of all legal pages (Admin).
     */
    public function index(Request $request): Response
    {
        $this->authorize('manage legal pages');

        $pages = LegalPage::orderBy('slug')->get();

        return Inertia::render('Admin/LegalPages', [
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for editing the specified legal page (Admin).
     */
    public function edit(Request $request, LegalPage $page): Response
    {
        $this->authorize('manage legal pages');

        return Inertia::render('Admin/EditLegalPage', [
            'page' => $page,
        ]);
    }

    /**
     * Update the specified legal page in storage (Admin).
     */
    public function update(Request $request, LegalPage $page)
    {
        $this->authorize('manage legal pages');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_description' => 'nullable|string|max:500',
            'is_published' => 'boolean',
        ]);

        $page->update($validated);

        return redirect()->route('admin.legal-pages.index')
            ->with('success', 'Seite wurde erfolgreich aktualisiert.');
    }
}
