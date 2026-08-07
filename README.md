## Rate limiter mancante

### Scenario:
Creare ed eseguire uno script (es. in bash con curl) che lancia moltissime richieste sulla stessa rotta con il pericolo di un denial of service

### Mitigazione:
- Rate limiter su /careers/submit
- Rate limiter su /article/search
- Rate limiter globale

## Logging mancante per operazioni critiche

### Scenario:
Sui tentativi precedenti di DoS non si può risalire al colpevole violando il principiio di accountability e no repudiation

### Mitigazione:
Log di:
- login/registrazione/logout
- creazione/modifica/eliminazione articolo
- assegnazione/cambi di ruolo

## Operazioni critiche in post e non in get

### Scenario: 
Ci si espone a possibili attacchi CSRF portando in questo caso ad una vertical escalation of privileges.
Provare un attacco csrf creando un piccolo server php che visualizzi una pagina html in cui in background scatta una chiamata ajax ad una rotta potenzialmente critica e non protetta (es. /admin/{user}/set-admin). Partendo dal browser dell'utente è possibile che l'azione vada in porto in quanto l'utente ha i privilegi adeguati.

### Mitigazione
Cambiare da get a post, facendo i dovuti controlli

## Uso non corretto di fillable nei modelli

### Scenario 
Un utente malevolo può provare a indovinare campi tipici di ruoli utente tipo isAdmin, is_admin etc.. alterando il form dal browser 

### Mitigazione
Nella proprietà fillable del modello in questione inserire tutti solo i campi gestiti nel form

## ssrf attack per api delle news

### Scenario
Esiste la funzionalità di suggerimento news recenti in fase di scrittura dell'articolo per prendere ispirazione. E' presente un menu a scelta facilmente alterabile da ispeziona elemento. L'utente malintenzionato con un minimo di conoscenza del sistema cambia l'url e prova a far lanciare al server una richiesta che lui non sarebbe autorizzato.
Per esempio il server recupera dei dati sugli utenti da un altro server in esecuzione sulla porta 8001. 


### Mitigazione
Rimodellare la funzionalità in modo tale da non poter lasciare spazio di modifica dell'url da parte di utenti malevoli. Implementare o migliorare la validazione delgli input.

https://newsapi.org/docs/endpoints/top-headlines
NewsAPI - api key 5fbe92849d5648eabcbe072a1cf91473

## Stored XSS Attack

### Scenario
Durante la creazione di un articlo si può manomettere il body della richiesta con un tool tipo burpsuite in modalità proxy in modo da evitare l'auto escape eseguito dall'editor stesso e far arrivare alla funzionalità di creazione articolo uno script malevelo nel testo.
Questo script verra memorizzato ed eseguito quando un utente visualizza l'articolo infettato.
Supponiamo che ci sia una misconfiguration a livello di CORS (config/cors.php) che quindi permetta richieste da domini esterni, utile quando frontend e backend sono separati ma se non opportunamente configurato risulta essere un grave problema.

### Mitigazione
Creare un meccanismo che filtri il testo prima di salvarlo e per essere sicuri anche in fase di visualizzazione dell'articolo.

