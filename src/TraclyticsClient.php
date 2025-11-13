<?php

namespace Traclytics;

class TraclyticsClient
{
    private string $baseUrl;
    private string $projectKey;
    private string $accessToken;
    private int    $maxRetries;
    private int    $initialDelayMs;
    private float  $backoffFactor;
    private int    $maxDelayMs;
    private int    $timeoutMs;
    private string $userIdKey;
    private bool   $isHris;
    private string $departmentKey;
    private bool   $isEnabled;

    public function __construct(
        string $baseUrl,
        string $projectKey,
        string $accessToken,
        array  $options = []
    )
    {
        if (empty($baseUrl)) {
            throw new \InvalidArgumentException('Base URL cannot be empty');
        }
        if (empty($projectKey)) {
            throw new \InvalidArgumentException('Project key cannot be empty');
        }
        if (empty($accessToken)) {
            throw new \InvalidArgumentException('Access token cannot be empty');
        }

        $this->baseUrl        = rtrim($baseUrl, '/');
        $this->projectKey     = $projectKey;
        $this->accessToken    = $accessToken;
        $this->maxRetries     = max(0, $options['maxRetries'] ?? 3);
        $this->initialDelayMs = max(0, $options['initialDelayMs'] ?? 400);
        $this->backoffFactor  = max(1.0, $options['backoffFactor'] ?? 2.0);
        $this->maxDelayMs     = max(0, $options['maxDelayMs'] ?? 8000);
        $this->timeoutMs      = max(1000, $options['timeoutMs'] ?? 10000);
        $this->userIdKey      = $options['userIdKey'] ?? 'user_id';
        $this->isHris         = $options['isHris'] ?? false;
        $this->departmentKey  = $options['departmentKey'] ?? 'department';
        $this->isEnabled      = $options['isEnabled'] ?? true;
    }

    /**
     * Track an event (alias for trackEvent)
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function track(array $payload): array
    {
        return $this->trackEvent($payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function trackEvent(array $payload): array
    {
        // If tracking is disabled, return early without sending the event
        if (!$this->isEnabled) {
            return [
                'status'  => 'success',
                'code'    => 200,
                'data'    => null,
                'message' => 'Event tracking is disabled',
            ];
        }

        $url = $this->baseUrl . '/events';

        // Set defaults for common fields
        $defaults = [
            'occurred_at' => gmdate('c', time() + 6 * 3600),
            'user_id'     => $this->detectUserId(),
            'platform'    => $this->detectPlatform(),
            'device'      => $this->detectDevice(),
            'browser'     => $this->detectBrowser(),
            'isHris'      => $this->isHris,
            'department'  => null,
        ];

        // Add department if HRIS mode is disabled
        // If HRIS mode is enabled, department gets on the server side
        if (!$this->isHris) {
            $department             = $this->detectDepartment();
            $defaults['department'] = $department;
        }

        // Merge defaults with payload, payload takes precedence
        $payload = array_merge($defaults, $payload);

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($jsonPayload === false) {
            return [
                'status'  => 'failed',
                'code'    => 0,
                'data'    => null,
                'message' => 'Invalid payload: ' . json_last_error_msg(),
                'errors'  => null,
            ];
        }

        return $this->requestWithRetry($url, 'POST', $jsonPayload);
    }

    /**
     * Detect the platform from the User-Agent
     */
    private function detectPlatform(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (stripos($userAgent, 'Mac') !== false || stripos($userAgent, 'Macintosh') !== false) {
            return 'MacOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
            return 'iOS';
        } elseif (stripos($userAgent, 'PostmanRuntime') !== false) {
            return 'Postman';
        }

        return 'Unknown';
    }

    /**
     * Detect the device type from the User-Agent
     */
    private function detectDevice(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) {
            return 'Mobile';
        } elseif (stripos($userAgent, 'Tablet') !== false || stripos($userAgent, 'iPad') !== false) {
            return 'Tablet';
        } elseif (stripos($userAgent, 'iPhone') !== false) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * Detect the browser from the User-Agent
     */
    private function detectBrowser(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (stripos($userAgent, 'Edg') !== false || stripos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Edg') === false) {
            return 'Chrome';
        } elseif (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (stripos($userAgent, 'MSIE') !== false || stripos($userAgent, 'Trident') !== false) {
            return 'Internet Explorer';
        } elseif (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'OPR') !== false) {
            return 'Opera';
        } elseif (stripos($userAgent, 'PostmanRuntime') !== false) {
            return 'Postman';
        }

        return 'Unknown';
    }

    /**
     * Detect the authenticated user ID
     * Supports Laravel Auth, session-based auth, and custom implementations
     * Returns null if no authenticated user is found
     */
    private function detectUserId(): ?string
    {
        // Try Laravel Auth facade (if available)
        if (class_exists('\Illuminate\Support\Facades\Auth')) {
            try {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user) {
                    // Try configured user ID key first
                    if (isset($user->{$this->userIdKey})) {
                        return (string) $user->{$this->userIdKey};
                    }
                    // Fallback to getAuthIdentifier method
                    if (method_exists($user, 'getAuthIdentifier')) {
                        return (string) $user->getAuthIdentifier();
                    }
                    // Fallback to id property
                    if (isset($user->id)) {
                        return (string) $user->id;
                    }
                }
            } catch (\Exception $e) {
                // Silent fail, continue to other methods
            }
        }

        // Try session-based user_id with configured key
        if (session_status() === PHP_SESSION_ACTIVE || (session_status() === PHP_SESSION_NONE && @session_start())) {
            if (isset($_SESSION[$this->userIdKey])) {
                return (string) $_SESSION[$this->userIdKey];
            }
            // Fallback to common keys
            if (isset($_SESSION['user_id'])) {
                return (string) $_SESSION['user_id'];
            }
            if (isset($_SESSION['id'])) {
                return (string) $_SESSION['id'];
            }
            if (isset($_SESSION['userId'])) {
                return (string) $_SESSION['userId'];
            }
        }

        // No authenticated user found
        return null;
    }

    /**
     * Detect the user's department
     * Only used when HRIS mode is enabled
     * Returns null if no department is found
     */
    private function detectDepartment(): ?string
    {
        // Try Laravel Auth facade (if available)
        if (class_exists('\Illuminate\Support\Facades\Auth')) {
            try {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user && isset($user->{$this->departmentKey})) {
                    return (string) $user->{$this->departmentKey};
                }
            } catch (\Exception $e) {
                // Silent fail, continue to other methods
            }
        }

        // Try session-based department
        if (session_status() === PHP_SESSION_ACTIVE || (session_status() === PHP_SESSION_NONE && @session_start())) {
            if (isset($_SESSION[$this->departmentKey])) {
                return (string) $_SESSION[$this->departmentKey];
            }
        }

        // No department found
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestWithRetry(string $url, string $method, ?string $body = null): array
    {
        $attempt = 0;
        $delay   = $this->initialDelayMs;

        while (true) {
            $ch      = curl_init();
            $headers = [
                'X-Project-Key: ' . $this->projectKey,
                'X-Access-Token: ' . $this->accessToken,
                'Content-Type: application/json',
                'Accept: application/json',
            ];

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT_MS     => $this->timeoutMs,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT      => 'TraclyticsClient/1.0.0',
            ]);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $responseBody = curl_exec($ch);
            $errno        = curl_errno($ch);
            $error        = curl_error($ch);
            $status       = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno !== 0) {
                // Network or timeout error
                if ($attempt >= $this->maxRetries) {
                    return [
                        'status'  => 'failed',
                        'code'    => 0,
                        'data'    => null,
                        'message' => 'Network error: ' . ($error ?: 'Unknown cURL error'),
                        'errors'  => null,
                    ];
                }
            } else {
                if ($status >= 200 && $status < 300) {
                    $decoded = json_decode($responseBody ?: 'null', true);
                    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                        return [
                            'status'  => 'failed',
                            'code'    => $status,
                            'data'    => null,
                            'message' => 'Invalid JSON response from server',
                            'errors'  => null,
                        ];
                    }
                    return $decoded ?? [];
                }

                $retryable = $status === 429 || $status >= 500;
                if (!$retryable || $attempt >= $this->maxRetries) {
                    // Try to parse response body as JSON with the server's structure
                    $decodedError = null;
                    if (is_string($responseBody) && $responseBody !== '') {
                        $decodedError = json_decode($responseBody, true);
                    }

                    if (is_array($decodedError)) {
                        // Assume server already returns structured error
                        return $decodedError;
                    }

                    $message = is_string($responseBody) && $responseBody !== ''
                        ? trim($responseBody)
                        : 'HTTP error';

                    return [
                        'status'  => 'failed',
                        'code'    => $status,
                        'data'    => null,
                        'message' => $message,
                        'errors'  => null,
                    ];
                }
            }

            // Backoff
            $attempt += 1;
            $jitter  = (mt_rand(875, 1125)) / 1000.0; // +/- ~12.5%
            $sleepMs = (int) min($delay * $jitter, $this->maxDelayMs);
            usleep($sleepMs * 1000);
            $delay = (int) min($delay * $this->backoffFactor, $this->maxDelayMs);
        }
    }
}