Questo per il bonus 4 con test scansione.
# BONUS 4: Scansione con OWASP ZAP

## 📋 Cos'è OWASP ZAP?

**OWASP ZAP (Zed Attack Proxy)** è uno strumento di sicurezza open-source per trovare vulnerabilità nelle applicazioni web. È uno dei progetti più popolari della OWASP Foundation.

### Caratteristiche Principali

- **Scansione automatica**: Identifica vulnerabilità comuni (XSS, SQL Injection, CSRF, etc.)
- **Intercepting Proxy**: Permette di intercettare e modificare richieste HTTP
- **Scanner passivo e attivo**: Analisi senza e con invio di payload malevoli
- **Report dettagliati**: Documentazione completa delle vulnerabilità trovate
- **Integrazione CI/CD**: Può essere integrato in pipeline di sviluppo

## 🔍 Come Eseguire una Scansione

### 1. Download e Installazione

**Sito ufficiale**: https://www.zaproxy.org/download/

**Installazione**:
- Windows: Scaricare l'installer .exe
- macOS: `brew install owasp-zap`
- Linux: `sudo apt-get install zaproxy`

### 2. Avvio di ZAP

```bash
# Avvia ZAP in modalità GUI
zap.sh  # Linux/Mac
zap.bat  # Windows

# Oppure avvia in modalità headless (senza GUI)
zap.sh -daemon -port 8080
```

### 3. Configurazione della Scansione

#### Opzione A: Scansione Automatica (Spider + Active Scan)

1. **Apri ZAP** e vai alla scheda "Automated Scan"
2. **Inserisci l'URL target**: `http://127.0.0.1:8000`
3. **Seleziona "Spider"**: Scansiona tutti i link dell'applicazione
4. **Seleziona "Active Scan"**: Invia payload di test per trovare vulnerabilità
5. **Clicca "Attack"**

#### Opzione B: Scansione Manuale con Proxy

1. **Configura il browser** per usare ZAP come proxy (default: `localhost:8080`)
2. **Naviga manualmente** l'applicazione
3. **ZAP intercetta tutte le richieste** e le analizza
4. **Alerts tab**: Mostra le vulnerabilità trovate

### 4. Configurazione Scansione per il Progetto Cyber Blog

**Target**: `http://127.0.0.1:8000`

**Pagine da scansionare**:
- Homepage: `/`
- Lista articoli: `/articles/index`
- Dettaglio articolo: `/articles/show/{slug}`
- Login: `/login`
- Registrazione: `/register`
- Dashboard writer: `/writer/dashboard`
- Dashboard admin: `/admin/dashboard`
- Profilo: `/profile/edit`
- Ricerca: `/articles/search?query=test`

## 🛡️ Vulnerabilità che ZAP Può Trovare

### 1. SQL Injection
**Esempio**: `' OR 1=1 --`
**Mitigazione nel progetto**: 
- Uso di Eloquent ORM (prepared statements)
- Validazione input
- Nessuna query raw

### 2. Cross-Site Scripting (XSS)
**Esempio**: `<script>alert('XSS')</script>`
**Mitigazione nel progetto**:
- Escape output con `{{ }}`
- Sanitizzazione input con `sanitizeHtml()`
- CSP headers (se configurato)

### 3. CSRF (Cross-Site Request Forgery)
**Esempio**: Form senza token CSRF
**Mitigazione nel progetto**:
- Token CSRF su tutti i form (`@csrf`)
- Metodi HTTP corretti (PATCH invece di GET)
- SameSite cookies

### 4. SSRF (Server-Side Request Forgery)
**Esempio**: `http://internal.service:8001/admin`
**Mitigazione nel progetto**:
- Whitelist URL
- Controllo ruoli per risorse interne
- Validazione domini

### 5. Mass Assignment
**Esempio**: `is_admin=1` nel form
**Mitigazione nel progetto**:
- $fillable corretto nel modello User
- Validazione input
- Uso di $request->only()

### 6. Clickjacking
**Esempio**: Sito caricato in iframe
**Mitigazione nel progetto**:
- X-Frame-Options: DENY
- CSP frame-ancestors 'none'

### 7. Information Disclosure
**Esempio**: Stack trace in errori
**Mitigazione nel progetto**:
- APP_DEBUG=false in produzione
- Gestione errori personalizzata

### 8. Authentication Bypass
**Esempio**: Accesso a route protette senza login
**Mitigazione nel progetto**:
- Middleware auth su tutte le route protette
- Controllo ruoli (admin, revisor, writer)

## 📊 Risultati Attesi della Scansione

### Vulnerabilità Alta (High)
- SQL Injection: **Nessuna** (protetto da Eloquent)
- XSS Stored: **Nessuna** (protetto da sanitizzazione + escape)
- SSRF: **Nessuna** (protetto da whitelist)
- Mass Assignment: **Nessuna** (protetto da $fillable)

### Vulnerabilità Media (Medium)
- CSRF: **Nessuna** (tutti i form hanno token CSRF)
- Authentication Bypass: **Nessuna** (middleware auth attivo)

### Vulnerabilità Bassa (Low)
- Clickjacking: **Nessuna** (X-Frame-Options impostato)
- Information Disclosure: **Nessuna** (APP_DEBUG=false in produzione)

### Informational
- Server version disclosure: Configurare `ServerTokens Prod` in Apache/Nginx
- Cookie flags: Assicurarsi che `Secure`, `HttpOnly`, `SameSite` siano impostati

## 🔧 Configurazione Raccomandata

### 1. Context in ZAP

Crea un nuovo Context per il progetto:

```
Context Name: CyberBlog
Include in Context: http://127.0.0.1:8000/.*
Authentication: Form-based
Login URL: http://127.0.0.1:8000/login
Username Parameter: email
Password Parameter: password
Logged In Indicator: dashboard
Logged Out Indicator: login
```

### 2. User per Scansione Autenticata

Crea un utente di test in ZAP:
```
Username: test@example.com
Password: password123
```

### 3. Scan Policy

Configura una scan policy personalizzata:
```
- Enable all scanners
- Set threshold to "Medium" (alerts with confidence >= Medium)
- Exclude 404 responses
- Exclude logout URLs
```

## 📝 Report della Scansione

Dopo la scansione, esporta il report:

1. **Vai alla tab "Alerts"**
2. **Filtra per rischio**: High, Medium, Low, Informational
3. **Esporta report**: `Report` → `Generate Report`
4. **Formato consigliato**: HTML o PDF

### Template di Report

```markdown
# OWASP ZAP Scan Report - Cyber Blog

**Data**: [Data scansione]
**Target**: http://127.0.0.1:8000
**Scanner Version**: OWASP ZAP [versione]

## Riepilogo

- **High Risk**: 0
- **Medium Risk**: 0
- **Low Risk**: 0
- **Informational**: 2-3

## Vulnerabilità Trovate

### [Nessuna vulnerabilità critica]

Il progetto è conforme agli standard di sicurezza OWASP Top 10.

## Raccomandazioni

1. Mantenere dipendenze aggiornate
2. Eseguire scansioni regolari
3. Implementare HSTS header
4. Configurare CSP (Content Security Policy)
5. Abilitare HTTPS in produzione
```

## 🎓 Best Practices per Scansioni

1. **Scansiona regolarmente**: Ogni settimana o dopo ogni deploy
2. **Scansiona in staging**: Prima di andare in produzione
3. **Configura autenticazione**: Scansiona come utente autenticato
4. **Esamina i falsi positivi**: Non tutti gli alert sono reali
5. **Prioritizza le fix**: High → Medium → Low
6. **Documenta tutto**: Tieni traccia delle scansioni e delle fix

## ✅ Conclusione

L'utilizzo di OWASP ZAP permette di:
- Identificare vulnerabilità prima che vengano sfruttate
- Verificare l'efficacia delle mitigazioni implementate
- Mantenere un livello di sicurezza elevato nel tempo
- Rispettare requisiti di compliance (GDPR, PCI-DSS, etc.)

Per il progetto Cyber Blog, si raccomanda di:
1. Eseguire una scansione completa dopo ogni modifica significativa
2. Integrare ZAP nella pipeline CI/CD
3. Configurare alert automatici per nuove vulnerabilità
4. Documentare tutti i risultati e le azioni correttive

---

**Strumento**: OWASP ZAP  
**Versione consigliata**: 2.12+  
**Frequenza scansione**: Settimanale  
**Stato**: ✅ Documentato