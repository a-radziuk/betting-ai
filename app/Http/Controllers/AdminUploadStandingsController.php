<?php

namespace App\Http\Controllers;

use App\Services\StandingsImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use InvalidArgumentException;
use JsonException;

class AdminUploadStandingsController extends Controller
{
    public function show(): View
    {
        return view('admin.upload-standings');
    }

    public function store(Request $request, StandingsImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'max:2048'],
            'payload' => ['nullable', 'string'],
        ]);

        $json = $this->resolvePayloadJson($request);
        if ($json === '') {
            return $this->redirectWithPayloadError($request, __('Provide a JSON file or paste standings export JSON.'));
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->redirectWithPayloadError($request, __('Invalid JSON.'));
        }

        if (! is_array($decoded)) {
            return $this->redirectWithPayloadError($request, __('Invalid JSON.'));
        }

        try {
            $tournament = $importService->import($decoded);
        } catch (InvalidArgumentException $e) {
            return $this->redirectWithPayloadError($request, $e->getMessage());
        }

        return redirect()
            ->route('admin.upload-standings')
            ->with('status', __('Imported standings for tournament :id (:name).', [
                'id' => $tournament->id,
                'name' => $tournament->name,
            ]));
    }

    private function resolvePayloadJson(Request $request): string
    {
        $file = $request->file('file');
        if ($file instanceof UploadedFile) {
            return (string) $file->get();
        }

        return trim((string) $request->input('payload', ''));
    }

    private function redirectWithPayloadError(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.upload-standings')
            ->withErrors(['payload' => $message])
            ->withInput();
    }
}
