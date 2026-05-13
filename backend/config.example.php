<?php
/**
 * CryptoValid – SMTP konfiguráció
 *
 * MÁSOLD ÁT config.php néven, és töltsd ki az adataidat.
 * (A config.php a .gitignore-ban van, így nem kerül publikus repo-ba.)
 */

return [

    // -------------------------------------------------------------------
    //  SMTP szerver beállítások
    // -------------------------------------------------------------------
    'smtp' => [
        // SMTP szerver hosztneve (pl. smtp.gmail.com, smtp.mailgun.org,
        // smtp.sendgrid.net, smtp.office365.com, smtp.zoho.eu, vagy a saját
        // tárhelyszolgáltatód SMTP-je, pl. mail.cryptovalid.eu)
        'host'       => 'smtp.example.com',

        // Port: 587 (STARTTLS, ajánlott) vagy 465 (SMTPS)
        'port'       => 587,

        // 'tls' (STARTTLS – port 587-hez) VAGY 'ssl' (SMTPS – port 465-höz)
        'encryption' => 'tls',

        // Bejelentkezési adatok
        'username'   => 'no-reply@cryptovalid.eu',
        'password'   => '',

        // Feladó adatok (ezzel a címmel megy ki az email)
        'from_email' => 'no-reply@cryptovalid.eu',
        'from_name'  => 'CryptoValid',
    ],

    // -------------------------------------------------------------------
    //  Címzett – ide érkeznek be az érdeklődések
    // -------------------------------------------------------------------
    'recipient' => [
        'email' => 'info@cryptovalid.eu',
        'name'  => 'CryptoValid Csapat',
    ],

    // -------------------------------------------------------------------
    //  Biztonság / egyebek
    // -------------------------------------------------------------------

    // Engedélyezett origin-ek (CORS). Hagyd '*'-on, ha az index.html
    // ugyanarról a domain-ről jön. Külön domain esetén add meg pontosan,
    // pl. ['https://cryptovalid.eu', 'https://www.cryptovalid.eu']
    'allowed_origins' => ['*'],

    // Egyszerű rate-limit (másodperc): ennyit kell várni két küldés között
    // ugyanarról az IP-ről. 0 = kikapcsolva.
    'rate_limit_seconds' => 30,

    // Log fájl helye (relatív vagy abszolút). Üres = nincs naplózás.
    'log_file' => __DIR__ . '/send.log',

    // Debug üzemmód – fejlesztéskor true, élesben false!
    // True esetén a kliens kap részletes SMTP hibákat.
    'debug' => false,
];
