<?php

namespace App\Http\Controllers;

use App\Services\EventAnalysisImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use JsonException;

class AdminUploadAnalysisController extends Controller
{
    public function show(): View
    {
        return view('admin.upload-analysis');
    }

    public function store(Request $request, EventAnalysisImportService $importService): RedirectResponse
    {
        $json = (string) $request->input('payload', '');

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->redirectWithPayloadError(__('Invalid JSON.'));
        }

        if (! is_array($decoded)) {
            return $this->redirectWithPayloadError(__('Invalid JSON.'));
        }

        try {
            $result = $importService->importList($decoded);
        } catch (InvalidArgumentException $e) {
            return $this->redirectWithPayloadError($e->getMessage());
        }

        return redirect()
            ->route('admin.upload-analysis')
            ->with(
                'status',
                __('Imported :imported analysis(es); skipped :skipped.', [
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                ])
            );
    }

    private function redirectWithPayloadError(string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.upload-analysis')
            ->withErrors(['payload' => $message])
            ->withInput();
    }
}
