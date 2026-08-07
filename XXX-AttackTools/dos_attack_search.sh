#!/bin/bash

# Script per testare attacco DoS sulla rotta /articles/search
# Questo script simula un attacco di Denial of Service inviando molteplici richieste
# consecutive alla rotta di ricerca articoli

echo "=========================================="
echo "Script Attacco DoS - /articles/search"
echo "=========================================="
echo ""

# Configurazione
TARGET_URL="http://127.0.0.1:8000/articles/search"
NUM_REQUESTS=100
DELAY=0.1  # Delay tra richieste in secondi (0.1 = 100ms)

echo "Target: $TARGET_URL"
echo "Numero di richieste: $NUM_REQUESTS"
echo "Delay tra richieste: ${DELAY}s"
echo ""
echo "Premi CTRL+C per interrompere in qualsiasi momento"
echo "Avvio attacco in 3 secondi..."
sleep 3
echo ""

# Contatori
count=0
blocked=0
success=0

# Esegui richieste
for i in $(seq 1 $NUM_REQUESTS); do
    # Invia richiesta e cattura il codice HTTP
    response=$(curl -s -o /dev/null -w "%{http_code}" "$TARGET_URL?q=test$i")
    
    # Incrementa contatore
    count=$((count + 1))
    
    # Classifica risposta
    if [ "$response" == "429" ]; then
        blocked=$((blocked + 1))
        echo "[$i/$NUM_REQUESTS] HTTP $response - RICHIESTA BLOCCATA (Rate Limit)"
    elif [ "$response" == "200" ]; then
        success=$((success + 1))
        echo "[$i/$NUM_REQUESTS] HTTP $response - OK"
    else
        echo "[$i/$NUM_REQUESTS] HTTP $response - Altro"
    fi
    
    # Attendi prima della prossima richiesta
    sleep $DELAY
done

echo ""
echo "=========================================="
echo "Risultati Attacco"
echo "=========================================="
echo "Totale richieste: $count"
echo "Richieste riuscite: $success"
echo "Richieste bloccate (429): $blocked"
echo "Altre risposte: $((count - success - blocked))"
echo ""

if [ $blocked -gt 0 ]; then
    echo "✓ Rate Limiter FUNZIONANTE - Attacco bloccato dopo $((success + 1)) richieste"
else
    echo "✗ Rate Limiter NON FUNZIONANTE - Tutte le richieste sono passate"
fi

echo ""
echo "Ora prova ad accedere manualmente a:"
echo "$TARGET_URL"
echo "Dovresti ricevere errore 429 se il rate limiter è attivo"
