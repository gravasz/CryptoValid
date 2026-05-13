# CryptoValid – Backend (SMTP email küldés)

Ez a mappa tartalmazza azt a kis PHP backendet, ami a landing oldal űrlapjának adatait SMTP-n keresztül elküldi az `info@cryptovalid.eu` címre.

## Fájlok

- `config.example.php` – konfig sablon (SMTP adatok, címzett, biztonsági beállítások)
- `config.php` – **TE HOZOD LÉTRE** a `config.example.php` alapján (lásd lentebb). Nem kerül commit-ra.
- `send.php` – maga az endpoint, ami fogadja a form POST-ot, validál és küld.
- `send.log` – automatikusan jön létre, küldések naplója (ha `log_file` ki van töltve).

## Telepítés (3 lépés)

### 1. Másold a backend mappát a tárhelyedre

A teljes `backend/` mappát töltsd fel a webszerveredre. Például, ha az index.html a `public_html/` gyökerében van:

```
public_html/
├── index.html
├── assets/
└── backend/
    ├── config.php          ← te hozod létre
    ├── send.php
    └── ...
```

### 2. PHPMailer telepítése

Két lehetőség:

**A) Composer-rel (ajánlott, ha van SSH hozzáférésed):**
```bash
cd backend
composer require phpmailer/phpmailer
```
Ez létrehoz egy `vendor/` mappát, ezt is töltsd fel.

**B) Kézzel, composer nélkül:**
1. Töltsd le a PHPMailer-t innen: https://github.com/PHPMailer/PHPMailer/releases (a legutóbbi `.zip`)
2. Csomagold ki, és másold a `src/` mappát a `backend/PHPMailer/src/` útvonalra. Így a `send.php` ezt látja:
   ```
   backend/PHPMailer/src/PHPMailer.php
   backend/PHPMailer/src/SMTP.php
   backend/PHPMailer/src/Exception.php
   ```

### 3. Konfig kitöltése

Másold át a `config.example.php`-t `config.php` névre, és töltsd ki az SMTP adataidat:

```php
'smtp' => [
    'host'       => 'mail.cryptovalid.eu',   // a tárhelyszolgáltatód SMTP-je
    'port'       => 587,
    'encryption' => 'tls',                    // 'tls' vagy 'ssl'
    'username'   => 'no-reply@cryptovalid.eu',
    'password'   => 'IDE-A-JELSZAVAD',
    'from_email' => 'no-reply@cryptovalid.eu',
    'from_name'  => 'CryptoValid',
],
'recipient' => [
    'email' => 'info@cryptovalid.eu',
    'name'  => 'CryptoValid Csapat',
],
```

### 4. Tesztelés

Nyisd meg a böngészőben: `https://cryptovalid.eu/index.html`
Töltsd ki valamelyik csempe form-ját, küldd el.
Ha minden jó, az `info@cryptovalid.eu`-ra megjön az email.

Hiba esetén:
- Ideiglenesen állítsd be `'debug' => true` a configban → a kliens részletes SMTP hibát kap
- Nézd meg a `send.log` fájlt
- Ellenőrizd a tárhely PHP `error_log`-ját

## Biztonsági tippek

- A `config.php` **soha ne** kerüljön publikus repóba (Git-be). A `.gitignore` a projekt gyökerében már kezeli ezt.
- Élesben mindig `'debug' => false` legyen.
- Ha külön domainre kerül a frontend és a backend, az `allowed_origins`-ban add meg pontosan a frontend domain-jét (ne `*`).
- A `rate_limit_seconds` egy egyszerű spam-szűrő – 30 mp alatt egy IP nem küldhet újra.
- Az űrlap honeypot mezőt (`website`) is kezel – ha bot tölti ki, csendben elnyeli.

## Általános SMTP szolgáltatók példa-beállításai

| Szolgáltató        | Host                    | Port | Encryption |
|--------------------|-------------------------|------|------------|
| Gmail (App Password) | smtp.gmail.com        | 587  | tls        |
| Office 365         | smtp.office365.com      | 587  | tls        |
| Zoho EU            | smtp.zoho.eu            | 587  | tls        |
| Mailgun            | smtp.mailgun.org        | 587  | tls        |
| SendGrid           | smtp.sendgrid.net       | 587  | tls        |
| SMTP2GO            | mail.smtp2go.com        | 2525 | tls        |
| Saját cPanel       | mail.cryptovalid.eu     | 465  | ssl        |

## API formátum (referencia)

A frontend ezt küldi POST-tal a `send.php`-ra (Content-Type: `application/json`):

```json
{
  "name": "Kovács Anna",
  "email": "anna@pelda.hu",
  "flow": "Fiat to Crypto",
  "sourceLabel": "Forrás IBAN szám",
  "source": "HU42 1177 3016 1111 1018 0000 0000",
  "destLabel": "Cél pénztárca cím",
  "dest": "0x...",
  "sourceTrust": "—",
  "destTrust": "Ethereum · megbízható"
}
```

Válasz:
- `200 { "ok": true }` – siker
- `400 { "error": "..." }` – validációs hiba
- `429 { "error": "..." }` – rate-limit
- `500 { "error": "..." }` – SMTP / szerver hiba
