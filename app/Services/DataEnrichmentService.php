<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataEnrichmentService
{
    /**
     * Enrich lead data from external APIs
     * 
     * Supported providers:
     * - Clearbit (clearbit.com)
     * - ZoomInfo (zoominfo.com)
     * - Hunter.io (hunter.io)
     * 
     * @param string $email Lead email
     * @param string|null $company Company name
     * @return array Enriched data
     */
    public function enrich(string $email, ?string $company = null): array
    {
        $enrichmentData = [
            'employees' => null,
            'industry' => null,
            'location' => null,
            'country' => null,
            'job_title' => null,
            'technologies' => [],
            'keywords' => [],
            'description' => null,
            'website' => null,
            'phone' => null,
            'linkedin' => null,
            'provider' => null,
        ];

        // Try Clearbit first (if API key is configured)
        if (config('services.clearbit.api_key')) {
            $clearbitData = $this->enrichWithClearbit($email, $company);
            if ($clearbitData) {
                $enrichmentData = array_merge($enrichmentData, $clearbitData);
                $enrichmentData['provider'] = 'clearbit';
            }
        }

        // Try ZoomInfo (if API key is configured)
        if (config('services.zoominfo.api_key') && !$enrichmentData['provider']) {
            $zoominfoData = $this->enrichWithZoomInfo($email, $company);
            if ($zoominfoData) {
                $enrichmentData = array_merge($enrichmentData, $zoominfoData);
                $enrichmentData['provider'] = 'zoominfo';
            }
        }

        // Try Hunter.io for email verification (if API key is configured)
        if (config('services.hunter.api_key')) {
            $hunterData = $this->verifyEmailWithHunter($email);
            if ($hunterData) {
                $enrichmentData = array_merge($enrichmentData, $hunterData);
            }
        }

        return $enrichmentData;
    }

    /**
     * Enrich with Clearbit API
     */
    private function enrichWithClearbit(string $email, ?string $company = null): ?array
    {
        try {
            $apiKey = config('services.clearbit.api_key');
            if (!$apiKey) {
                return null;
            }

            // Person enrichment
            $personResponse = Http::withBasicAuth($apiKey, '')
                ->get("https://person.clearbit.com/v2/combined/find", [
                    'email' => $email,
                ]);

            if (!$personResponse->successful()) {
                return null;
            }

            $personData = $personResponse->json();
            $companyData = $personData['company'] ?? [];

            return [
                'employees' => $companyData['metrics']['employees'] ?? null,
                'industry' => $companyData['category']['industry'] ?? null,
                'location' => $companyData['geo']['city'] ?? null,
                'country' => $companyData['geo']['country'] ?? null,
                'job_title' => $personData['employment']['title'] ?? null,
                'technologies' => $companyData['tech'] ?? [],
                'description' => $companyData['description'] ?? null,
                'website' => $companyData['domain'] ?? null,
                'linkedin' => $companyData['linkedin']['handle'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Clearbit enrichment failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Enrich with ZoomInfo API
     */
    private function enrichWithZoomInfo(string $email, ?string $company = null): ?array
    {
        try {
            $apiKey = config('services.zoominfo.api_key');
            if (!$apiKey) {
                return null;
            }

            // ZoomInfo API integration
            // Note: This is a placeholder - adjust based on ZoomInfo API documentation
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get('https://api.zoominfo.com/search/contact', [
                'email' => $email,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            
            return [
                'employees' => $data['company']['employeeCount'] ?? null,
                'industry' => $data['company']['industry'] ?? null,
                'location' => $data['location']['city'] ?? null,
                'country' => $data['location']['country'] ?? null,
                'job_title' => $data['jobTitle'] ?? null,
                'website' => $data['company']['website'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('ZoomInfo enrichment failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify email with Hunter.io
     */
    private function verifyEmailWithHunter(string $email): ?array
    {
        try {
            $apiKey = config('services.hunter.api_key');
            if (!$apiKey) {
                return null;
            }

            $response = Http::get('https://api.hunter.io/v2/email-verifier', [
                'email' => $email,
                'api_key' => $apiKey,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json()['data'] ?? [];

            return [
                'email_verified' => $data['result'] === 'deliverable',
                'email_score' => $data['score'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Hunter.io verification failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get enrichment providers status
     */
    public function getProvidersStatus(): array
    {
        return [
            'clearbit' => [
                'enabled' => !empty(config('services.clearbit.api_key')),
                'name' => 'Clearbit',
            ],
            'zoominfo' => [
                'enabled' => !empty(config('services.zoominfo.api_key')),
                'name' => 'ZoomInfo',
            ],
            'hunter' => [
                'enabled' => !empty(config('services.hunter.api_key')),
                'name' => 'Hunter.io',
            ],
        ];
    }
}

