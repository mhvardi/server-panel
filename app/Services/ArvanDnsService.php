<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArvanDnsService
{
    private string $apiKey;
    private string $apiUrl;
    private string $serverIp;

    public function __construct()
    {
        $this->apiKey = config('services.arvan.api_key', '');
        $this->apiUrl = config('services.arvan.api_url', 'https://napi.arvancloud.ir/cdn/4.0');
        $this->serverIp = env('SERVER_IP', '');
    }

    /**
     * Validate subdomain name according to best practices
     */
    public function validateSubdomainName(string $subdomain): array
    {
        $subdomain = strtolower(trim($subdomain));

        // Basic validation: only letters, numbers, and hyphens
        if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
            return [
                'valid' => false,
                'error' => 'Subdomain must contain only letters, numbers, and hyphens. Minimum 3 characters.',
            ];
        }

        // Minimum length check
        if (strlen($subdomain) < 3) {
            return [
                'valid' => false,
                'error' => 'Subdomain must be at least 3 characters long.',
            ];
        }

        // Forbidden names list
        $forbidden = ['www', 'mail', 'ftp', 'api', 'admin', 'panel', 'cdn', 'static', 'test'];

        if (in_array($subdomain, $forbidden)) {
            return [
                'valid' => false,
                'error' => "Subdomain '{$subdomain}' is not allowed. Please choose a different name.",
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if DNS record already exists for a subdomain
     */
    public function checkExistingRecord(string $domain, string $subdomain): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'apikey ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get("{$this->apiUrl}/domains/{$domain}/dns-records");

            if (!$response->successful()) {
                Log::warning('Failed to check existing DNS records', [
                    'domain' => $domain,
                    'subdomain' => $subdomain,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false; // Assume not exists if API fails
            }

            $records = $response->json('data', []);

            foreach ($records as $record) {
                if (
                    isset($record['type']) &&
                    strtolower($record['type']) === 'a' &&
                    isset($record['name']) &&
                    strtolower($record['name']) === strtolower($subdomain)
                ) {
                    return true; // Record exists
                }
            }

            return false; // Record does not exist
        } catch (\Exception $e) {
            Log::error('Exception while checking DNS records', [
                'domain' => $domain,
                'subdomain' => $subdomain,
                'error' => $e->getMessage(),
            ]);
            return false; // Assume not exists on error
        }
    }

    /**
     * Create A record for subdomain
     */
    public function createARecord(string $domain, string $subdomain, ?string $ip = null): array
    {
        $ip = $ip ?: $this->serverIp;

        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Arvan API key is not configured. Please set ARVAN_API_KEY in .env',
            ];
        }

        if (empty($ip)) {
            return [
                'success' => false,
                'error' => 'Server IP is not configured. Please set SERVER_IP in .env',
            ];
        }

        // Validate subdomain name
        $validation = $this->validateSubdomainName($subdomain);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
            ];
        }

        // Check if record already exists
        if ($this->checkExistingRecord($domain, $subdomain)) {
            return [
                'success' => false,
                'error' => "DNS record for '{$subdomain}.{$domain}' already exists.",
            ];
        }

        try {
            // Validate IP format
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return [
                    'success' => false,
                    'error' => "Invalid IP address format: {$ip}",
                ];
            }

            // Prepare request payload
            // Based on API error: "value must be an array"
            // Format: value should be an array containing objects with 'ip' key
            $payload = [
                'type' => 'A',
                'name' => $subdomain,
                'value' => [
                    [
                        'ip' => $ip,
                    ],
                ],
                'ttl' => 120,
                'cloud' => false,
            ];

            $url = "{$this->apiUrl}/domains/{$domain}/dns-records";

            // Log request details for debugging
            Log::info('Creating DNS A record', [
                'url' => $url,
                'domain' => $domain,
                'subdomain' => $subdomain,
                'ip' => $ip,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'apikey ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            // If first attempt fails, try alternative formats
            if (!$response->successful() && in_array($response->status(), [400, 422])) {
                $responseJson = $response->json();
                $errorMessage = $responseJson['message'] ?? '';
                $errors = $responseJson['errors'] ?? [];

                Log::info('First attempt failed, trying alternative formats', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'errors' => $errors,
                ]);

                // Try format 2: value as array of IP strings (without object wrapper)
                if (isset($errors['value']) || str_contains(strtolower($errorMessage), 'value')) {
                    Log::info('Trying alternative DNS record format (value as array of strings)', [
                        'domain' => $domain,
                        'subdomain' => $subdomain,
                    ]);

                    $payloadAlt1 = [
                        'type' => 'A',
                        'name' => $subdomain,
                        'value' => [$ip], // Array of IP strings
                        'ttl' => 120,
                        'cloud' => false,
                    ];

                    $response = Http::withHeaders([
                        'Authorization' => 'apikey ' . $this->apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])->post($url, $payloadAlt1);

                    // If still fails, try format 3: without cloud field
                    if (!$response->successful() && in_array($response->status(), [400, 422])) {
                        Log::info('Trying alternative DNS record format (without cloud field)', [
                            'domain' => $domain,
                            'subdomain' => $subdomain,
                        ]);

                        $payloadAlt2 = [
                            'type' => 'A',
                            'name' => $subdomain,
                            'value' => [
                                [
                                    'ip' => $ip,
                                ],
                            ],
                            'ttl' => 120,
                        ];

                        $response = Http::withHeaders([
                            'Authorization' => 'apikey ' . $this->apiKey,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])->post($url, $payloadAlt2);
                    }
                }
            }

            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseJson = $response->json();

            if ($response->successful()) {
                $data = $responseJson['data'] ?? [];
                Log::info('DNS A record created successfully', [
                    'domain' => $domain,
                    'subdomain' => $subdomain,
                    'ip' => $ip,
                    'record_id' => $data['id'] ?? null,
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'data' => $data,
                    'message' => $responseJson['message'] ?? 'DNS record created successfully.',
                ];
            }

            // Enhanced error logging
            Log::error('Failed to create DNS A record', [
                'domain' => $domain,
                'subdomain' => $subdomain,
                'ip' => $ip,
                'status' => $statusCode,
                'request_url' => $url,
                'request_payload' => $payload,
                'response_body' => $responseBody,
                'response_json' => $responseJson,
            ]);

            // Extract detailed error message
            $errorMessage = 'Unknown error';

            if (isset($responseJson['message'])) {
                $errorMessage = $responseJson['message'];
            } elseif (isset($responseJson['error'])) {
                $errorMessage = $responseJson['error'];
            } elseif (isset($responseJson['errors'])) {
                // Handle validation errors
                if (is_array($responseJson['errors'])) {
                    $errorMessages = [];
                    foreach ($responseJson['errors'] as $field => $messages) {
                        if (is_array($messages)) {
                            $errorMessages[] = $field . ': ' . implode(', ', $messages);
                        } else {
                            $errorMessages[] = $field . ': ' . $messages;
                        }
                    }
                    $errorMessage = implode(' | ', $errorMessages);
                } else {
                    $errorMessage = is_string($responseJson['errors'])
                        ? $responseJson['errors']
                        : json_encode($responseJson['errors']);
                }
            }

            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage, JSON_UNESCAPED_UNICODE);
            }

            return [
                'success' => false,
                'error' => "Failed to create DNS record: {$errorMessage}",
                'status' => $statusCode,
                'details' => $responseJson,
            ];
        } catch (\Exception $e) {
            Log::error('Exception while creating DNS A record', [
                'domain' => $domain,
                'subdomain' => $subdomain,
                'ip' => $ip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Exception occurred: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Health check: Verify if subdomain is accessible
     */
    public function healthCheck(string $fullDomain, int $timeout = 5, int $waitSeconds = 10): array
    {
        // Wait a bit for DNS propagation
        if ($waitSeconds > 0) {
            sleep($waitSeconds);
        }

        try {
            $url = "https://{$fullDomain}";
            $response = Http::timeout($timeout)->get($url);

            if ($response->successful() || $response->status() === 200) {
                return [
                    'success' => true,
                    'message' => "Domain {$fullDomain} is accessible.",
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'message' => "Domain {$fullDomain} returned status {$response->status()}.",
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            // Timeout or connection error is expected during DNS propagation
            Log::info('Health check failed (expected during DNS propagation)', [
                'domain' => $fullDomain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => "Domain may not be accessible yet (DNS propagation in progress). This is normal and may take a few minutes.",
                'error' => $e->getMessage(),
            ];
        }
    }
}
