<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HttpService
{
    protected $client;
    
    // Domini pubblici accessibili a tutti
    protected $publicDomains = ['newsapi.org'];
    
    // Domini interni - SOLO per admin
    protected $internalDomains = ['internal.finance'];
    
    // Tutti i domini permessi
    protected $allowedDomains = ['newsapi.org', 'internal.finance'];
    
    protected $allowedProtocols = ['http', 'https'];
    protected $refererHeader;

    public function __construct()
    {
        $this->refererHeader = config('app.url');
        $this->client = new Client();
    }

    public function getRequest($url)
    {
        $parsedUrl = parse_url($url);

        // Validate protocol
        if (!in_array($parsedUrl['scheme'], $this->allowedProtocols)) {
            $this->logSuspiciousRequest($url, 'Protocol not allowed');
            return 'Protocol not allowed';
        }
       
        // Validate domain
        if (!isset($parsedUrl['host']) || !in_array($parsedUrl['host'], $this->allowedDomains)) {
            $this->logSuspiciousRequest($url, 'Domain not allowed');
            return 'Domain not allowed';
        }

        // ✅ CONTROLLO RUOLI: Solo admin possono accedere a domini interni
        if (in_array($parsedUrl['host'], $this->internalDomains)) {
            if (!Auth::check() || !Auth::user()->is_admin) {
                $this->logSuspiciousRequest($url, 'Unauthorized access to internal resource');
                return 'Unauthorized: Only administrators can access internal resources';
            }
            
            // Log speciale per accesso a risorse interne
            Log::channel('audit')->alert('Admin accessed internal resource', [
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'url' => $url,
                'host' => $parsedUrl['host'],
                'ip_address' => request()->ip()
            ]);
        } else {
            // Log per risorse pubbliche
            Log::channel('audit')->info('Public API request', [
                'user_id' => Auth::id() ?? 'guest',
                'url' => $url,
                'host' => $parsedUrl['host']
            ]);
        }

        // Aggiungi l'intestazione Referer per le richieste al server locale
        $options['headers'] = ['Referer' => $this->refererHeader];

        try {
            $response = $this->client->request('GET', $url, $options);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            Log::channel('audit')->error('HTTP request failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return 'Something went wrong: ' . $e->getMessage();
        }
    }
    
    /**
     * Logga richieste sospette
     */
    private function logSuspiciousRequest(string $url, string $reason): void
    {
        Log::channel('audit')->warning('Suspicious HTTP request blocked', [
            'url' => $url,
            'reason' => $reason,
            'user_id' => Auth::id() ?? 'guest',
            'user_name' => Auth::user()->name ?? 'guest',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
