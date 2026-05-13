# CryptoValid

A **CryptoValid** egy egyszerű kriptotárca-validálási szolgáltatás landing oldala, amellyel a felhasználók ellenőriztethetik kriptotárcáik megbízhatóságát, legyen szó Fiat → Crypto, Crypto → Fiat, vagy Crypto → Crypto tranzakciókról.

## Projekt Felépítése

- `index.html`: A fő landing oldal egy reszponzív, modern dizájnnal, amely HTML-ből és tiszta CSS-ből áll. Beépített űrlapokkal és modal ablakokkal kezeli a felhasználói adatbevitelt.
- `assets/`: A vizuális elemek, képek, logók és a letölthető dokumentumok (pl. sajtóközlemény) mappája.
- `backend/`: Egy PHP alapú backend szolgáltatás, ami a kitöltött érdeklődéseket fogadja és SMTP segítségével (PHPMailer használatával) továbbítja a megadott e-mail címre.

## Lokális Fejlesztés és Futtatás (Docker)

A projekt támogatja a Docker segítségével történő azonnali futtatást, amely egy `php:8.2-apache` környezetet biztosít.

1. **Docker indítása:**
   ```bash
   docker compose up -d
   ```
   Az oldal az indítás után elérhető a [http://localhost:8080](http://localhost:8080) címen.

2. **Backend függőségek telepítése:**
   A backend a PHP-hez beépítetten a Composert használja. Első indításnál telepíteni kell a csomagokat:
   ```bash
   docker compose exec app bash -c "cd backend && composer require phpmailer/phpmailer"
   ```

## Backend Beállítása Élesben

Ahhoz, hogy az e-mail küldés (az űrlapokból) működjön, be kell állítani az SMTP szervert.

1. Lépj a `backend/` mappába.
2. Másold a `config.example.php` fájlt `config.php` névre. *(Ez a fájl rejtve marad a publikus GitHub repódból).*
3. Töltsd ki a `config.php` fájlban az SMTP szervered adatait, a bejelentkezési nevet, jelszót, és a cél e-mail címet.

## Technológiai Stack

- **Frontend:** Vanilla HTML, CSS, JavaScript (Nincs külső framework, mint a React vagy Vue). Modern vizuális megoldásokkal (glassmorphism, CSS animációk).
- **Backend:** PHP 8.2+
- **Infrastruktúra:** Docker & Docker Compose
- **Analitika:** Google Analytics beépítve.
