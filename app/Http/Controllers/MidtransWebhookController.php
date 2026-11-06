<?php

namespace App\Http\Controllers;

use App\Services\PembayaranService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    /**
     * Terima dan proses notifikasi dari Midtrans.
     * Rute ini dikecualikan dari CSRF (lihat bootstrap/app.php).
     */
    public function terima(Request $request, PembayaranService $service): JsonResponse
    {
        $payload = $request->all();

        $result = $service->prosesNotifikasi($payload);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
