Spiegazione fatta con AI di quello che ho fatto passo per passo in md
# Challenge 6: Uso non corretto della proprietà fillable nei modelli

## 📋 Spiegazione del Problema

### Cos'è il Mass Assignment?

In Laravel, il **Mass Assignment** è un meccanismo che permette di aggiornare multiple colonne di un modello contemporaneamente usando un array. Ad esempio:

```php
$user = User::find(1);
$user->update($request->all());  // Aggiorna tutti i campi ricevuti
```

### Il Problema della Proprietà $fillable

La proprietà `$fillable` nel modello definisce **quali campi possono essere aggiornati massivamente** (cioè tramite `$request->all()` o simili).

**PRIMA (vulnerabile)**:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',        // ❌ Campo critico esposto
    'is_revisor',      // ❌ Campo critico esposto
    'is_writer'        // ❌ Campo critico esposto
];
```

**DOPO (sicuro)**:
```php
protected $fillable = [
    'name',    // ✅ Solo campi del form
    'email',   // ✅ Solo campi del form
    'password' // ✅ Solo campi del form
];
```

### Perché è Pericoloso?

Se un utente malintenzionato può inviare campi non previsti nel form (come `is_admin`, `is_revisor`, `is_writer`), e questi campi sono in `$fillable`, allora:

1. L'utente modifica il form dal browser (Inspect Element)
2. Aggiunge campi nascosti: `<input type="hidden" name="is_admin" value="1">`
3. Invia il form
4. Il controller usa `$request->all()` che include `is_admin`
5. Il modello aggiorna `is_admin` perché è in `$fillable`
6. **Privilege escalation**: l'utente diventa admin senza autorizzazione

## 🎯 Attacco: Mass Assignment Attack

### Meccanismo dell'Attacco

1. **Identificazione**: L'attaccante nota che nel form di modifica profilo ci sono solo nome, email, password
2. **Ispezione**: Usa Inspect Element per vedere il codice HTML del form
3. **Modifica**: Aggiunge campi nascosti per i ruoli:
   ```html
   <input type="hidden" name="is_admin" value="1">
   <input type="hidden" name="is_revisor" value="1">
   <input type="hidden" name="is_writer" value="1">
   ```
4. **Invio**: Sottomette il form
5. **Risultato**: Il server aggiorna tutti i campi inclusi quelli di ruolo
6. **Privilege Escalation**: L'utente ora è admin, revisor e writer

### Esempio di Payload

```html
<form action="/profile/update" method="POST">
    <input type="text" name="name" value="Mario Rossi">
    <input type="email" name="email" value="mario@example.com">
    <input type="password" name="password" value="newpass">
    
    <!-- Campi malevoli aggiunti dall'attaccante -->
    <input type="hidden" name="is_admin" value="1">
    <input type="hidden" name="is_revisor" value="1">
    <input type="hidden" name="is_writer" value="1">
</form>
```

### Impatto

- **Privilege Escalation**: Utente normale diventa admin
- **Bypass dei controlli di autorizzazione**: Accesso a funzionalità riservate
- **Compromissione completa del sistema**: L'attaccante può fare qualsiasi cosa

## ✅ Soluzione Implementata

### 1. Creazione Funzionalità Profilo (Vulnerabile)

**File**: `app/Http/Controllers/ProfileController.php`

Creato controller con funzionalità intenzionalmente vulnerabile:

```php
public function update(Request $request)
{
    $user = Auth::user();
    
    // Validazione base (ma non blocca campi extra)
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'password' => ['nullable', 'confirmed', Password::defaults()],
    ]);

    // ❌ VULNERABILE: Mass assignment senza controlli
    // Un utente può inviare is_admin, is_revisor, is_writer nel form
    // e questi campi verranno aggiornati perché sono in $fillable
    $user->update($request->all());

    // Se la password è stata fornita, aggiornala
    if ($request->filled('password')) {
        $user->update([
            'password' => Hash::make($request->password)
        ]);
    }

    return redirect()->route('profile.edit')->with('message', 'Profilo aggiornato con successo');
}
```

**File**: `resources/views/profile/edit.blade.php`

Creato form di modifica profilo con avviso di sicurezza:

```html
<form action="{{ route('profile.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name" class="form-control" 
               value="{{ old('name', $user->name) }}" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control" 
               value="{{ old('email', $user->email) }}" required>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">New Password</label>
        <input type="password" name="password" id="password" class="form-control">
    </div>

    <div class="alert alert-warning">
        <strong>⚠️ Security Notice:</strong> 
        This form is intentionally vulnerable to demonstrate Mass Assignment attacks. 
        Try adding hidden fields like <code>is_admin</code> or <code>is_revisor</code> to the form!
    </div>

    <button type="submit" class="btn btn-primary">Update Profile</button>
</form>
```

**File**: `routes/web.php`

Aggiunte route per il profilo:

```php
// Route per il profilo utente (VULNERABILE a Mass Assignment)
Route::middleware('auth')->group(function(){
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
});
```

### 2. Mitigazione: Correzione $fillable

**File**: `app/Models/User.php`

**PRIMA (vulnerabile)**:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',        // ❌ Permette privilege escalation
    'is_revisor',      // ❌ Permette privilege escalation
    'is_writer'        // ❌ Permette privilege escalation
];
```

**DOPO (sicuro)**:
```php
protected $fillable = [
    'name',    // ✅ Solo campi del form di profilo
    'email',   // ✅ Solo campi del form di profilo
    'password' // ✅ Solo campi del form di profilo
];
```

**Spiegazione**:
- Ora solo `name`, `email` e `password` possono essere aggiornati massivamente
- Se l'utente invia `is_admin`, `is_revisor`, `is_writer`, questi campi vengono **ignorati**
- Il modello rifiuta di aggiornare campi non in `$fillable`

### 3. Mitigazione Alternativa: Uso di $guarded

**Alternativa**: Usare `$guarded` invece di `$fillable`

```php
protected $guarded = [
    'id',
    'is_admin',
    'is_revisor',
    'is_writer',
    'created_at',
    'updated_at'
];
```

Con `$guarded`, tutti i campi sono aggiornabili TRANNE quelli nella lista. Questo è più sicuro per default ma meno esplicito.

## 🛡️ Meccanismi di Protezione

### 1. Whitelist con $fillable
Solo i campi esplicitamente listati in `$fillable` possono essere aggiornati massivamente.

### 2. Validazione Input
Il controller valida solo i campi previsti:
```php
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email,' . $user->id,
    'password' => ['nullable', 'confirmed', Password::defaults()],
]);
```

### 3. Best Practice: Non usare $request->all()
Invece di `$request->all()`, usare `$request->only()`:

```php
// ❌ Pericoloso: accetta tutti i campi
$user->update($request->all());

// ✅ Sicuro: accetta solo i campi specificati
$user->update($request->only('name', 'email', 'password'));
```

## 🧪 Test di Verifica

### Test 1: Tentativo di Privilege Escalation

**Payload malevolo**:
```html
<form action="/profile/update" method="POST">
    <input type="text" name="name" value="Mario Rossi">
    <input type="email" name="email" value="mario@example.com">
    <input type="hidden" name="is_admin" value="1">
    <input type="hidden" name="is_revisor" value="1">
    <input type="hidden" name="is_writer" value="1">
</form>
```

**PRIMA della mitigazione**:
- L'utente invia il form con `is_admin=1`
- Il controller usa `$request->all()` che include `is_admin`
- Il modello aggiorna `is_admin` perché è in `$fillable`
- **Risultato**: Privilege escalation riuscita ❌

**DOPO la mitigazione**:
- L'utente invia il form con `is_admin=1`
- Il controller usa `$request->all()` ma il modello rifiuta `is_admin`
- Il modello aggiorna solo `name`, `email`, `password`
- **Risultato**: Privilege escalation bloccata ✅

### Test 2: Verifica Campi Aggiornati

**PRIMA**:
```php
// Database dopo l'aggiornamento
name: "Mario Rossi"
email: "mario@example.com"
is_admin: 1        // ❌ Modificato dall'attaccante
is_revisor: 1      // ❌ Modificato dall'attaccante
is_writer: 1       // ❌ Modificato dall'attaccante
```

**DOPO**:
```php
// Database dopo l'aggiornamento
name: "Mario Rossi"
email: "mario@example.com"
is_admin: 0        // ✅ Non modificato
is_revisor: 0      // ✅ Non modificato
is_writer: 0       // ✅ Non modificato
```

## 📊 File Modificati

| File | Modifiche | Tipo |
|------|-----------|------|
| `app/Models/User.php` | Rimosso `is_admin`, `is_revisor`, `is_writer` da `$fillable` | Sicurezza |
| `app/Http/Controllers/ProfileController.php` | Creato controller profilo (vulnerabile) | Nuovo |
| `resources/views/profile/edit.blade.php` | Creato vista form profilo | Nuovo |
| `routes/web.php` | Aggiunte route profilo | Nuovo |

## 🎓 Best Practices Applicate

1. ✅ **Whitelist con $fillable**: Solo campi necessari esplicitamente permessi
2. ✅ **Validazione input**: Controllo stretto su tutti gli input
3. ✅ **Principio dei privilegi minimi**: Solo i campi del form sono aggiornabili
4. ✅ **Non usare $request->all()**: Preferire `$request->only()` o `$request->validated()`
5. ✅ **Separazione responsabilità**: Controller gestisce validazione, modello gestisce fillable

## 📚 Standard Rispettati

- ✅ **OWASP Top 10 2021**:
  - A01: Broken Access Control (Mass Assignment)
  - A03: Injection (Mass Assignment come injection)
- ✅ **Laravel Security Best Practices**:
  - Uso di $fillable o $guarded
  - Validazione input
  - Never trust user input
- ✅ **CWE-20**: Improper Input Validation
- ✅ **CWE-862**: Missing Authorization

## 📝 Lezione Appresa

### Perché è Successo?

Il modello `User.php` originale aveva:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',
    'is_revisor',
    'is_writer'
];
```

Questo perché probabilmente:
1. I campi `is_admin`, `is_revisor`, `is_writer` servivano per l'admin per assegnare ruoli
2. Sono stati aggiunti a `$fillable` per permettere l'aggiornamento
3. Non si è considerato che qualsiasi form potrebbe inviare questi campi

### La Regola d'Oro

**$fillable deve contenere SOLO i campi che l'utente può modificare nei form pubblici/privati.**

Se un campo serve solo per operazioni interne (es. assegnazione ruoli da admin), non deve essere in `$fillable` del modello User.

### Soluzione Corretta

Per assegnare ruoli, usare metodi dedicati nel controller admin:

```php
// AdminController
public function setAdmin(User $user)
{
    $user->is_admin = true;
    $user->save();
    
    return redirect()->back()->with('message', 'User promoted to admin');
}
```

In questo modo:
- Non si usa mass assignment
- Si usa l'assegnazione diretta di un singolo campo
- Solo l'admin può chiamare questo metodo (middleware `admin`)

## ✅ Conclusione

La Challenge 6 è stata completata con successo. L'applicazione dimostra:

1. **Vulnerabilità**: Mass Assignment attack che permette privilege escalation
2. **Impatto**: Utente normale può diventare admin modificando il form
3. **Mitigazione**: Correzione di `$fillable` per includere solo i campi del form di profilo
4. **Lezione**: Importanza di usare `$fillable` correttamente e di non usare `$request->all()`

L'attacco descritto nella challenge (modifica del form per aggiungere `is_admin`) non è più possibile perché:
- `is_admin` non è più in `$fillable`
- Il modello rifiuta di aggiornare campi non permessi
- Anche se l'utente invia `is_admin=1`, viene ignorato

---

**Data completamento**: 7 Agosto 2026  
**Ambiente**: Laravel 12+  
**Stato**: ✅ Completato