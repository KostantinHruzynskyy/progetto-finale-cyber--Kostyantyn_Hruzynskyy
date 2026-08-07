<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra qualsiasi servizio dell'applicazione.
     */
    public function register(): void
    {
        //
    }

    /**
     * Esegue il bootstrap dell'applicazione.
     */
    public function boot(): void
    {
        // Rate limiter globale - 100 richieste al minuto per IP
        // Protegge le route pubbliche da attacchi DoS
        RateLimiter::for('global', function ($request) {
            return Limit::perMinute(config('rate-limiting.defaults.limit', 100))
                ->by($request->ip());
        });

        // Rate limiter per la ricerca articoli - 30 richieste al minuto per IP
        // La ricerca è costosa in termini di query al database
        RateLimiter::for('search', function ($request) {
            return Limit::perMinute(config('rate-limiting.limiters.search.limit', 30))
                ->by($request->ip());
        });

        // Rate limiter per l'invio candidature - 5 richieste al minuto per IP
        // Previene spam e invii massivi dal form pubblico
        RateLimiter::for('careers', function ($request) {
            return Limit::perMinute(config('rate-limiting.limiters.careers.limit', 5))
                ->by($request->ip());
        });

        // Rate limiter per il login di Fortify - 5 tentativi al minuto per email e IP
        // Protegge da attacchi brute force sul sistema di autenticazione
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(config('rate-limiting.limiters.login.limit', 5))
                ->by($request->input('email').'|'.$request->ip());
        });

        // Rate limiter per l'autenticazione a due fattori - 5 richieste al minuto
        RateLimiter::for('two-factor', function ($request) {
            return Limit::perMinute(config('rate-limiting.limiters.two-factor.limit', 5))
                ->by($request->session()->get('login.id'));
        });

        // Se la tabella categorie esiste, condivide le categorie con tutte le viste
        if(Schema::hasTable('categories')){
            $categories = Category::all();
            View::share(['categories' => $categories]);
        }
        // Se la tabella tags esiste, condivide i tags con tutte le viste
        if(Schema::hasTable('tags')){
            $tags = Tag::all();
            View::share(['tags' => $tags]);
        }
    }
}
