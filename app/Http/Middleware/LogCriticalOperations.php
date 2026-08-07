<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LogCriticalOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Debug: conferma che il middleware viene eseguito
        Log::channel('audit')->info('TEST: Middleware LogCriticalOperations eseguito', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
        
        // Esegui la richiesta prima di loggare (per catturare il risultato)
        $response = $next($request);
        
        // Logga solo se l'operazione è andata a buon fine (2xx, 3xx)
        // oppure se c'è un errore (4xx, 5xx) - vogliamo tracciare tutto
        $this->logCriticalOperation($request, $response);
        
        return $response;
    }
    
    /**
     * Logga l'operazione critica
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     * @return void
     */
    private function logCriticalOperation(Request $request, $response): void
    {
        $user = Auth::user();
        
        // Prepara i dati da loggare
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user ? $user->id : 'guest',
            'user_name' => $user ? $user->name : 'guest',
            'user_email' => $user ? $user->email : 'guest',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'status_code' => $response->getStatusCode(),
            'parameters' => $this->sanitizeParameters($request->all()),
        ];
        
        // Determina il tipo di operazione
        $operationType = $this->determineOperationType($request);
        
        // Logga l'operazione nel canale audit
        try {
            Log::channel('audit')->info("{$operationType} - Operazione critica eseguita", $logData);
        } catch (\Exception $e) {
            // Fallback al canale di default se audit non è configurato
            Log::info("{$operationType} - Operazione critica eseguita (fallback)", $logData);
        }
    }
    
    /**
     * Determina il tipo di operazione basandosi sulla rotta
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function determineOperationType(Request $request): string
    {
        $routeName = $request->route() ? $request->route()->getName() : '';
        
        // Login/Registrazione/Logout
        if (str_contains($routeName, 'login') || str_contains($routeName, 'register') || str_contains($routeName, 'logout')) {
            return 'AUTH';
        }
        
        // Operazioni su articoli
        if (str_contains($routeName, 'article') || str_contains($routeName, 'articles')) {
            if (str_contains($routeName, 'create') || str_contains($routeName, 'store')) {
                return 'ARTICLE_CREATE';
            }
            if (str_contains($routeName, 'edit') || str_contains($routeName, 'update')) {
                return 'ARTICLE_UPDATE';
            }
            if (str_contains($routeName, 'destroy')) {
                return 'ARTICLE_DELETE';
            }
            if (str_contains($routeName, 'accept')) {
                return 'ARTICLE_ACCEPT';
            }
            if (str_contains($routeName, 'reject')) {
                return 'ARTICLE_REJECT';
            }
            if (str_contains($routeName, 'undo')) {
                return 'ARTICLE_UNDO';
            }
        }
        
        // Operazioni su ruoli
        if (str_contains($routeName, 'set-admin') || str_contains($routeName, 'set-revisor') || str_contains($routeName, 'set-writer')) {
            if (str_contains($routeName, 'set-admin')) {
                return 'ROLE_SET_ADMIN';
            }
            if (str_contains($routeName, 'set-revisor')) {
                return 'ROLE_SET_REVISOR';
            }
            if (str_contains($routeName, 'set-writer')) {
                return 'ROLE_SET_WRITER';
            }
        }
        
        // Operazioni su tag e categorie
        if (str_contains($routeName, 'tag')) {
            if (str_contains($routeName, 'store')) {
                return 'TAG_CREATE';
            }
            if (str_contains($routeName, 'edit')) {
                return 'TAG_UPDATE';
            }
            if (str_contains($routeName, 'delete')) {
                return 'TAG_DELETE';
            }
        }
        
        if (str_contains($routeName, 'category')) {
            if (str_contains($routeName, 'store')) {
                return 'CATEGORY_CREATE';
            }
            if (str_contains($routeName, 'edit')) {
                return 'CATEGORY_UPDATE';
            }
            if (str_contains($routeName, 'delete')) {
                return 'CATEGORY_DELETE';
            }
        }
        
        return 'CRITICAL_OPERATION';
    }
    
    /**
     * Sanitizza i parametri rimuovendo dati sensibili
     *
     * @param  array  $parameters
     * @return array
     */
    private function sanitizeParameters(array $parameters): array
    {
        // Rimuovi campi sensibili dai log
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'old_password',
            'new_password',
            '_token',
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($parameters[$field])) {
                $parameters[$field] = '***REDACTED***';
            }
        }
        
        return $parameters;
    }
}