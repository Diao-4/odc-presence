# ODC Présence — Orange Digital Center · AWS re/Start P11 Après-midi

Application Laravel de contrôle des présences par badge QR Code.
Flux central : **Scanner → badge reconnu → nom affiché → heure serveur enregistrée → Présent / Retard → badge suivant.**

## 1. Installation (Laravel 12, PHP 8.2+, MySQL)

```bash
composer create-project laravel/laravel odc-presence
cd odc-presence

# Copier le contenu de cette archive par-dessus le projet (remplace les fichiers existants)
#   app/  config/odc.php  database/  resources/  routes/web.php

composer require barryvdh/laravel-dompdf endroid/qr-code maatwebsite/excel
```

Extensions PHP requises : `gd` (QR Code PNG), `zip` (archive des badges), `mbstring`, `pdo_mysql`.

### `.env`
```dotenv
APP_NAME="ODC Présence"
APP_URL=https://presence.example.ml
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

DB_CONNECTION=mysql
DB_DATABASE=odc_presence
DB_USERNAME=...
DB_PASSWORD=...

SESSION_DRIVER=database
```

### Fuseau horaire (essentiel : l'heure d'arrivée vient du serveur)
Dans `config/app.php` :
```php
'timezone' => 'Africa/Bamako',   // adaptez à la ville de la formation
'locale'   => 'fr',
```

### Base de données, stockage, assets
```bash
php artisan migrate
php artisan db:seed                      # paramètres + comptes admin / agent
php artisan db:seed --class=DemoSeeder   # (facultatif) 5 participants de démonstration
php artisan storage:link                 # photos des participants
npm install && npm run build             # Tailwind CSS 4 (déjà configuré par Laravel 12)
```

Comptes créés par le seeder (à changer immédiatement) :

| Rôle | Identifiant | Mot de passe |
|---|---|---|
| Administrateur | `admin` | `Admin@2026` |
| Agent | `agent` | `Agent@2026` |

Logo officiel : déposez `public/images/odc-logo.png`, il remplace automatiquement le carré « ODC ».

## 2. HTTPS obligatoire pour la caméra
Les navigateurs n'autorisent la caméra que sur **HTTPS** (ou `localhost`).
Pour tester sur un téléphone en local : `php artisan serve --host=0.0.0.0` derrière un tunnel HTTPS (ngrok, cloudflared) ou Laravel Herd/Valet avec `secure`.
En production : certificat Let's Encrypt.

Si la caméra est indisponible, l'agent peut saisir le numéro imprimé (`ODC-00452`) dans « Saisie manuelle » ; ces saisies sont tracées dans le journal.

## 3. Architecture

```
app/
├── Models/            User, Participant, Badge, Attendance, Setting, ActivityLog
├── Services/
│   ├── AttendanceService.php        ← cœur métier du scan (heure serveur, retard, double scan, départ)
│   ├── BadgeService.php             ← émission/remplacement, jeton QR, PDF A6, ZIP
│   ├── ReportService.php            ← statistiques, absences calculées, filtres, synthèses
│   ├── AttendanceExportService.php  ← exports PDF / XLSX / CSV + nommage des fichiers
│   ├── SettingService.php           ← paramètres en base (mis en cache)
│   └── ActivityLogger.php           ← journal des opérations
├── Http/Controllers/  Dashboard, Scan, Participant, Badge, Attendance, Report, Setting, User, Password, ActivityLog, Auth/Login
├── Http/Requests/     Login, Scan, Participant, User, Setting
├── Policies/          ParticipantPolicy
└── Exports/           AttendanceExport (Excel)
```

### Règles métier implémentées
- **Heure** : `now()` côté serveur uniquement ; le navigateur n'envoie que le contenu du QR Code.
- **Retard** : comparaison à la minute. Limite 08:00 → 08:00:59 = Présent, 08:01 = Retard 1 min, 08:35 = Retard 35 min.
- **Double scan** : index unique `(participant_id, date)` + verrou transactionnel → « Présence déjà enregistrée » avec la première heure.
- **Absences** : jamais stockées. Calculées pour chaque jour de formation passé, et pour aujourd'hui après l'heure limite.
- **QR Code** : contient seulement un jeton opaque (`ODC-BDG-8F72A91C4D2E`). Badge désactivé ou remplacé → « Badge invalide ou désactivé ».
- **Remplacement** : nouveau jeton, l'ancien badge passe à « Remplacé » et est refusé immédiatement.
- **Suppression d'un participant** : logique (soft delete), l'historique est conservé.
- **Départ** : mode activable dans Paramètres ; ajoute un sélecteur Arrivée / Départ au scanner.

### Droits
| | Agent | Admin |
|---|:-:|:-:|
| Tableau de bord, scanner, présences, recherche et fiche participant | ✓ | ✓ |
| Création / modification / désactivation participants | | ✓ |
| Badges (PDF, ZIP, impression, remplacement) | | ✓ |
| Exports, rapports, paramètres, utilisateurs, journal | | ✓ |

Contrôle par `Gate::define('admin')` (middleware `can:admin` dans `routes/web.php`) + `ParticipantPolicy`.

## 4. Fichiers produits
- `ODC_AWS_ReStart_P11_ApresMidi_Badges.zip` — un PDF A6 par participant (`Amadou_Traore.pdf`…)
- `ODC_AWS_ReStart_P11_ApresMidi_Badges.pdf` — tous les badges, un par page
- Impression : page HTML A4, 4 badges par feuille avec traits de découpe
- `ODC_AWS_ReStart_P11_Presences_01-10-2026.pdf` / `.xlsx` / `.csv` (ou `_01-10-2026_07-10-2026` pour une période)
