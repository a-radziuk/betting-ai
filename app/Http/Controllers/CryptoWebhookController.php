<?php

namespace App\Http\Controllers;

use App\Services\CryptoWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CryptoWebhookController extends Controller
{
    public function __invoke(Request $request, CryptoWebhookService $webhook): Response
    {
        $payload = $request->json()->all()
            ?: $request->request->all();

        if ($payload !== []) {
            $webhook->handle($payload);
        }

        return response('OK', 200);
    }
}
