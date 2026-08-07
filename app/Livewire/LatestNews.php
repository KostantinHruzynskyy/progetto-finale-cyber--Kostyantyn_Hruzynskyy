<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\HttpService;
use Illuminate\Support\Facades\Auth;

class LatestNews extends Component
{
    // Whitelist di URL predefiniti - nessun input utente
    protected $allowedApis = [
        'it' => 'https://newsapi.org/v2/top-headlines?country=it&apiKey=',
        'gb' => 'https://newsapi.org/v2/top-headlines?country=gb&apiKey=',
        'us' => 'https://newsapi.org/v2/top-headlines?country=us&apiKey=',
    ];
    
    public $selectedCountry;
    public $news;
    protected $httpService;

    public function __construct()
    {
        $this->httpService = app(HttpService::class);
    }

    public function fetchNews()
    {
        // Validazione input
        if (!isset($this->selectedCountry) || !array_key_exists($this->selectedCountry, $this->allowedApis)) {
            $this->news = ['error' => 'Invalid country selection'];
            return;
        }

        // Costruisci URL dalla whitelist (nessun input utente)
        $apiKey = config('services.newsapi.key');
        $url = $this->allowedApis[$this->selectedCountry] . $apiKey;

        // Verifica che l'utente sia autenticato
        if (!Auth::check()) {
            $this->news = ['error' => 'Authentication required'];
            return;
        }

        // Effettua la richiesta
        $response = $this->httpService->getRequest($url);
        $this->news = json_decode($response, true);

        // Log dell'operazione
        if (isset($this->news['error'])) {
            \Illuminate\Support\Facades\Log::channel('audit')->warning('NewsAPI request failed', [
                'user_id' => Auth::id(),
                'country' => $this->selectedCountry,
                'error' => $this->news['error']
            ]);
        }
    }
    
    public function render()
    {
        return view('livewire.latest-news');
    }
}
