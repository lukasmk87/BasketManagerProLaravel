<?php

namespace App\Http\Controllers;

use App\Services\UserImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class UserImportController extends Controller
{
    protected UserImportService $importService;

    public function __construct(UserImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Show the import page.
     */
    public function index(): Response
    {
        $this->authorize('create users');

        return Inertia::render('Admin/Users/Import', [
            'preview' => session('import_preview'),
        ]);
    }

    /**
     * Download CSV template.
     */
    public function downloadTemplate()
    {
        $this->authorize('create users');

        $csvContent = $this->importService->generateTemplate();

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ]);
    }

    /**
     * Upload and preview import file.
     */
    public function upload(Request $request)
    {
        $this->authorize('create users');

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // Max 10MB
        ]);

        try {
            // Parse file
            $data = $this->importService->parseFile($request->file('file'));

            if ($data->isEmpty()) {
                return back()->with('error', 'Die hochgeladene Datei enthält keine Daten.');
            }

            // Validate and preview
            $preview = $this->importService->validateAndPreview($data);

            // Store preview in session
            session(['import_preview' => $preview]);

            return back()->with('success',
                "{$preview['valid']} von {$preview['total']} Zeilen sind valide und können importiert werden.");

        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Verarbeiten der Datei: ' . $e->getMessage());
        }
    }

    /**
     * Execute the import.
     */
    public function import(Request $request)
    {
        $this->authorize('create users');

        $preview = session('import_preview');

        if (!$preview || empty($preview['valid_rows'])) {
            return back()->with('error', 'Keine validen Daten zum Importieren gefunden. Bitte laden Sie zuerst eine Datei hoch.');
        }

        try {
            $result = $this->importService->importUsers(
                $preview['valid_rows'],
                auth()->user()->name
            );

            // Clear preview from session
            session()->forget('import_preview');

            if ($result['success']) {
                $message = "{$result['imported']} Benutzer wurden erfolgreich importiert.";

                if ($result['failed'] > 0) {
                    $message .= " {$result['failed']} Benutzer konnten nicht importiert werden.";
                }

                return redirect()->route('admin.users.index')->with('success', $message);
            } else {
                return back()->with('error', $result['error']);
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Import fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Cancel import and clear preview.
     */
    public function cancel()
    {
        $this->authorize('create users');

        session()->forget('import_preview');

        return redirect()->route('admin.users.import.index')
            ->with('success', 'Import wurde abgebrochen.');
    }
}
