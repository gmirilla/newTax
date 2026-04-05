<?php

namespace App\Services\FIRS;

use App\Models\FirsApiLog;
use App\Models\TenantFirsCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * HTTP client for the FIRS e-Invoicing API.
 *
 * All requests are signed with a HMAC-SHA256 signature using the tenant's
 * secret key.  Every call is logged to firs_api_logs (immutable audit trail).
 *
 * Base URL is read from config('services.firs.base_url').
 */
class FirsApiClient
{
    private string $baseUrl;

    public function __construct(
        private readonly TenantFirsCredential $credential
    ) {
        $this->baseUrl = rtrim(config('services.firs.base_url', ''), '/');
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Submit an invoice payload for FIRS structural validation.
     *
     * @param  array  $payload  UBL 2.1 JSON payload
     * @return array            FIRS validation response body
     * @throws RuntimeException on HTTP or API error
     */
    public function validateInvoice(array $payload): array
    {
        return $this->post('/api/v1/invoices/validate', $payload);
    }

    /**
     * Sign a validated invoice and retrieve IRN/CSID/QR from FIRS.
     *
     * @param  array  $payload  UBL 2.1 JSON payload (same as validateInvoice)
     * @return array            FIRS signing response: {irn, csid, qrCode, ...}
     * @throws RuntimeException on HTTP or API error
     */
    public function signInvoice(array $payload): array
    {
        return $this->post('/api/v1/invoices/sign', $payload);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function post(string $endpoint, array $payload): array
    {
        $responseStatus = null;
        $responseBody   = null;
        $invoiceId      = $payload['invoice_id'] ?? null; // optional contextual field

        try {
            $response = Http::withHeaders($this->buildHeaders($payload))
                ->timeout(config('services.firs.timeout', 30))
                ->retry(config('services.firs.retries', 2), 1000)
                ->post($this->baseUrl . $endpoint, $payload);

            $responseStatus = $response->status();
            $responseBody   = $response->json() ?? [];

            FirsApiLog::record(
                tenantId:        $this->credential->tenant_id,
                invoiceId:       $invoiceId,
                endpoint:        $endpoint,
                requestPayload:  $this->sanitiseForLog($payload),
                responseStatus:  $responseStatus,
                responseBody:    $responseBody,
            );

            $response->throw();

            return $responseBody;

        } catch (RequestException $e) {
            // HTTP 4xx/5xx — response already logged above
            $msg = $responseBody['message'] ?? $responseBody['error'] ?? "FIRS API error on {$endpoint}";
            throw new RuntimeException("[FIRS] {$msg} (HTTP {$responseStatus})", $responseStatus, $e);

        } catch (ConnectionException $e) {
            // Network-level failure — log without response
            FirsApiLog::record(
                tenantId:       $this->credential->tenant_id,
                invoiceId:      $invoiceId,
                endpoint:       $endpoint,
                requestPayload: $this->sanitiseForLog($payload),
                responseStatus: null,
                responseBody:   ['error' => $e->getMessage()],
            );
            throw new RuntimeException("[FIRS] Connection failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Build required headers for each FIRS API request.
     * Authentication: API Key in header + HMAC-SHA256 request signature.
     */
    private function buildHeaders(array $payload): array
    {
        $timestamp = (string) now()->timestamp;
        $body      = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $timestamp . $body, $this->credential->secret_key);

        return [
            'Content-Type'     => 'application/json',
            'Accept'           => 'application/json',
            'X-Service-ID'     => $this->credential->service_id,
            'X-API-Key'        => $this->credential->api_key,
            'X-Timestamp'      => $timestamp,
            'X-Signature'      => $signature,
        ];
    }

    /**
     * Strip any large binary fields (public_key, certificate) from log payloads.
     */
    private function sanitiseForLog(array $payload): array
    {
        return array_filter($payload, fn($v) => ! (is_string($v) && strlen($v) > 2000));
    }
}
