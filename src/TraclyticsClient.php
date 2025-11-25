<?php

namespace Traclytics;

class TraclyticsClient
{
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $projectKey;
    /** @var string */
    private $accessToken;
    /** @var int */
    private $maxRetries;
    /** @var int */
    private $initialDelayMs;
    /** @var float */
    private $backoffFactor;
    /** @var int */
    private $maxDelayMs;
    /** @var int */
    private $timeoutMs;
    /** @var string */
    private $userIdKey;
    /** @var string */
    private $userNameKey;
    /** @var bool */
    private $isHris;
    /** @var string */
    private $departmentKey;
    /** @var bool */
    private $isEnabled;

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
        $this->timeoutMs      = max(200, $options['timeoutMs'] ?? 2000);
        $this->userIdKey      = $options['userIdKey'] ?? 'user_id';
        $this->userNameKey    = $options['userNameKey'] ?? 'user_name';
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
            'user_name'   => $this->detectUserName(),
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

        if (empty($userAgent)) {
            return 'Others';
        }

        // iOS's devices (check before Mac since iPad/iPhone can contain Mac in UA)
        if (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false || stripos($userAgent, 'iPod') !== false) {
            return 'iOS';
        }

        // Android (check before Linux)
        if (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        }

        // Windows (check various versions)
        if (stripos($userAgent, 'Windows NT 10.0') !== false || stripos($userAgent, 'Windows 10') !== false) {
            return 'Windows';
        }
        if (stripos($userAgent, 'Windows NT 6.3') !== false || stripos($userAgent, 'Windows 8.1') !== false) {
            return 'Windows';
        }
        if (stripos($userAgent, 'Windows NT 6.2') !== false || stripos($userAgent, 'Windows 8') !== false) {
            return 'Windows';
        }
        if (stripos($userAgent, 'Windows NT 6.1') !== false || stripos($userAgent, 'Windows 7') !== false) {
            return 'Windows';
        }
        if (stripos($userAgent, 'Windows NT 6.0') !== false || stripos($userAgent, 'Windows Vista') !== false) {
            return 'Windows';
        }
        if (stripos($userAgent, 'Windows NT 5.1') !== false || stripos($userAgent, 'Windows XP') !== false) {
            return 'Windows';
        }
        if (stripos($userAgent, 'Windows') !== false) {
            return 'Windows';
        }

        // macOS (check various versions and identifiers)
        if (stripos($userAgent, 'Mac OS X') !== false || stripos($userAgent, 'Macintosh') !== false || stripos($userAgent, 'Mac_PowerPC') !== false) {
            return 'MacOS';
        }
        if (stripos($userAgent, 'Mac') !== false && stripos($userAgent, 'iPhone') === false && stripos($userAgent, 'iPad') === false) {
            return 'MacOS';
        }

        // Linux distributions
        if (stripos($userAgent, 'Ubuntu') !== false) {
            return 'Linux';
        }
        if (stripos($userAgent, 'Debian') !== false) {
            return 'Linux';
        }
        if (stripos($userAgent, 'Fedora') !== false) {
            return 'Linux';
        }
        if (stripos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }

        // Chrome OS
        if (stripos($userAgent, 'CrOS') !== false || stripos($userAgent, 'Chromium OS') !== false) {
            return 'Chrome OS';
        }

        // BlackBerry
        if (stripos($userAgent, 'BlackBerry') !== false || stripos($userAgent, 'BB10') !== false) {
            return 'BlackBerry';
        }

        // Windows Phone
        if (stripos($userAgent, 'Windows Phone') !== false) {
            return 'Windows Phone';
        }

        // Symbian
        if (stripos($userAgent, 'Symbian') !== false) {
            return 'Symbian';
        }

        // Postman and API tools
        if (stripos($userAgent, 'PostmanRuntime') !== false || stripos($userAgent, 'Postman') !== false) {
            return 'Postman';
        }
        if (stripos($userAgent, 'curl') !== false) {
            return 'cURL';
        }
        if (stripos($userAgent, 'Wget') !== false) {
            return 'Wget';
        }

        // Bot/Crawler detection (optional - you might want to handle these differently)
        if (stripos($userAgent, 'bot') !== false || stripos($userAgent, 'crawler') !== false || stripos($userAgent, 'spider') !== false) {
            return 'Bot';
        }

        return 'Others';
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
     * Order matters: check more specific browsers first
     */
    private function detectBrowser(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (empty($userAgent)) {
            return 'Others';
        }

        // Postman and API tools (check first)
        if (stripos($userAgent, 'PostmanRuntime') !== false || stripos($userAgent, 'Postman') !== false) {
            return 'Postman';
        }
        if (stripos($userAgent, 'curl') !== false) {
            return 'cURL';
        }
        if (stripos($userAgent, 'Wget') !== false) {
            return 'Wget';
        }

        // Edge (Chromium-based) - check before Chrome since Edge UA contains Chrome
        if (stripos($userAgent, 'Edg/') !== false || stripos($userAgent, 'EdgA') !== false || stripos($userAgent, 'EdgiOS') !== false) {
            return 'Edge';
        }
        // Legacy Edge (EdgeHTML)
        if (stripos($userAgent, 'Edge') !== false && stripos($userAgent, 'Chrome') === false) {
            return 'Edge';
        }

        // Opera - check before Chrome since Opera UA contains Chrome
        if (stripos($userAgent, 'OPR/') !== false || stripos($userAgent, 'Opera/') !== false) {
            return 'Opera';
        }
        if (stripos($userAgent, 'Opera') !== false) {
            return 'Opera';
        }

        // Vivaldi - check before Chrome since Vivaldi UA contains Chrome
        if (stripos($userAgent, 'Vivaldi') !== false) {
            return 'Vivaldi';
        }

        // Brave - check before Chrome since Brave UA contains Chrome
        if (stripos($userAgent, 'Brave') !== false) {
            return 'Brave';
        }

        // Samsung Internet - check before Chrome since Samsung UA contains Chrome
        if (stripos($userAgent, 'SamsungBrowser') !== false) {
            return 'Samsung Internet';
        }

        // Yandex Browser - check before Chrome since Yandex UA contains Chrome
        if (stripos($userAgent, 'YaBrowser') !== false) {
            return 'Yandex Browser';
        }

        // UC Browser - check before Chrome since UC Browser UA may contain Chrome
        if (stripos($userAgent, 'UCBrowser') !== false || stripos($userAgent, 'UC Browser') !== false) {
            return 'UC Browser';
        }

        // Chrome (check after Edge/Opera/Vivaldi/Brave since they contain Chrome in UA)
        if (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Edg') === false &&
            stripos($userAgent, 'OPR') === false && stripos($userAgent, 'Opera') === false &&
            stripos($userAgent, 'Vivaldi') === false && stripos($userAgent, 'Brave') === false &&
            stripos($userAgent, 'SamsungBrowser') === false && stripos($userAgent, 'YaBrowser') === false &&
            stripos($userAgent, 'UCBrowser') === false) {
            return 'Chrome';
        }

        // Firefox (check various versions)
        if (stripos($userAgent, 'Firefox') !== false || stripos($userAgent, 'FxiOS') !== false) {
            return 'Firefox';
        }

        // Safari (check after Chrome since Safari UA may contain Chrome on iOS)
        if (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false &&
            stripos($userAgent, 'Chromium') === false) {
            return 'Safari';
        }

        // Internet Explorer (legacy)
        if (stripos($userAgent, 'MSIE') !== false || stripos($userAgent, 'Trident/') !== false) {
            return 'Internet Explorer';
        }

        // Internet Explorer Mobile
        if (stripos($userAgent, 'IEMobile') !== false) {
            return 'Internet Explorer Mobile';
        }

        // Maxthon
        if (stripos($userAgent, 'Maxthon') !== false) {
            return 'Maxthon';
        }

        // QQ Browser
        if (stripos($userAgent, 'QQBrowser') !== false || stripos($userAgent, 'QQ/') !== false) {
            return 'QQ Browser';
        }

        // Baidu Browser
        if (stripos($userAgent, 'Baidu') !== false && stripos($userAgent, 'Baiduspider') === false) {
            return 'Baidu Browser';
        }

        // 360 Browser
        if (stripos($userAgent, '360SE') !== false || stripos($userAgent, '360EE') !== false) {
            return '360 Browser';
        }

        // Chromium (check after Chrome-based browsers)
        if (stripos($userAgent, 'Chromium') !== false) {
            return 'Chromium';
        }

        // Bot/Crawler detection
        if (stripos($userAgent, 'bot') !== false || stripos($userAgent, 'crawler') !== false ||
            stripos($userAgent, 'spider') !== false || stripos($userAgent, 'Googlebot') !== false ||
            stripos($userAgent, 'Bingbot') !== false || stripos($userAgent, 'Slurp') !== false) {
            return 'Bot';
        }

        return 'Others';
    }

    /**
     * Detect the authenticated user ID
     * Supports Laravel Auth, session helper function, session-based auth, and custom implementations
     * Returns null if no authenticated user is found
     *
     * @return string|null
     */
    private function detectUserId()
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

        // Try session helper function (if available) - e.g., session('user_id')
        if (function_exists('session')) {
            try {
                $userId = session($this->userIdKey);
                if ($userId !== null && $userId !== '') {
                    return (string) $userId;
                }
                // Fallback to common keys
                $userId = session('user_id');
                if ($userId !== null && $userId !== '') {
                    return (string) $userId;
                }
                $userId = session('id');
                if ($userId !== null && $userId !== '') {
                    return (string) $userId;
                }
                $userId = session('userId');
                if ($userId !== null && $userId !== '') {
                    return (string) $userId;
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
     * Detect the authenticated user name
     * Supports Laravel Auth, session helper function, session-based auth, and custom implementations
     * Returns null if no authenticated user is found
     *
     * @return string|null
     */
    private function detectUserName()
    {
        // Try Laravel Auth facade (if available)
        if (class_exists('\Illuminate\Support\Facades\Auth')) {
            try {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user) {
                    // Try configured user name key first
                    if (isset($user->{$this->userNameKey})) {
                        return (string) $user->{$this->userNameKey};
                    }
                    // Fallback to common name fields
                    if (isset($user->name)) {
                        return (string) $user->name;
                    }
                    if (isset($user->username)) {
                        return (string) $user->username;
                    }
                    if (isset($user->user_name)) {
                        return (string) $user->user_name;
                    }
                }
            } catch (\Exception $e) {
                // Silent fail, continue to other methods
            }
        }

        // Try session helper function (if available) - e.g., session('user_name')
        if (function_exists('session')) {
            try {
                $userName = session($this->userNameKey);
                if ($userName !== null && $userName !== '') {
                    return (string) $userName;
                }
                // Fallback to common keys
                $userName = session('user_name');
                if ($userName !== null && $userName !== '') {
                    return (string) $userName;
                }
                $userName = session('name');
                if ($userName !== null && $userName !== '') {
                    return (string) $userName;
                }
                $userName = session('username');
                if ($userName !== null && $userName !== '') {
                    return (string) $userName;
                }
            } catch (\Exception $e) {
                // Silent fail, continue to other methods
            }
        }

        // Try session-based user_name with configured key
        if (session_status() === PHP_SESSION_ACTIVE || (session_status() === PHP_SESSION_NONE && @session_start())) {
            if (isset($_SESSION[$this->userNameKey])) {
                return (string) $_SESSION[$this->userNameKey];
            }
            // Fallback to common keys
            if (isset($_SESSION['user_name'])) {
                return (string) $_SESSION['user_name'];
            }
            if (isset($_SESSION['name'])) {
                return (string) $_SESSION['name'];
            }
            if (isset($_SESSION['username'])) {
                return (string) $_SESSION['username'];
            }
        }

        // No authenticated user name found
        return null;
    }

    /**
     * Detect the user's department
     * Only used when HRIS mode is enabled
     * Returns null if no department is found
     *
     * @return string|null
     */
    private function detectDepartment()
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
     * @param string      $url
     * @param string      $method
     * @param string|null $body
     * @return array<string, mixed>
     */
    private function requestWithRetry(string $url, string $method, $body = null)
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
                CURLOPT_USERAGENT      => 'TraclyticsClient/1.4.0',
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