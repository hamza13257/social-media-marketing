# Démarrage MVP (publication incluse)

## 1. Utiliser `main` (pas une vieille branche phase-3)

```bash
git checkout main
git pull origin main
```

La publication (phase 4+) est sur **`main`**.

## 2. Backend

### Windows + PHP 8.x : `could not find driver` / `pdo_mysql` bloqué

Si `.env` utilise **MySQL** mais tu vois *Application Control policy has blocked* sur `php_pdo_mysql.dll`, Laravel ne peut pas se connecter.

**Le plus simple en local** (comme `.env.example`) :

```powershell
cd backend
# Dans .env :
#   DB_CONNECTION=sqlite
#   (commente DB_HOST, DB_PORT, DB_DATABASE mysql, DB_USERNAME, DB_PASSWORD)
New-Item -ItemType File -Force -Path database\database.sqlite
php artisan config:clear
php artisan migrate
```

Pour MySQL plus tard : Laragon/XAMPP, ou autoriser `php_pdo_mysql.dll` dans la stratégie Windows (WDAC), et `extension=pdo_mysql` dans `php.ini`.

### Installation standard

```bash
cd backend
composer install
cp .env.example .env   # si besoin
php artisan key:generate
touch database/database.sqlite   # si sqlite
php artisan migrate
php artisan db:seed --class=SubscriptionPlanSeeder
```

## 3. Trois terminaux pour la publication

```bash
# T1 — API
php artisan serve

# T2 — jobs (obligatoire pour "Publish now")
php artisan queue:work

# T3 — posts planifiés (optionnel)
php artisan schedule:work
```

Sans **T2**, le bouton *Publish now* reste en file d’attente.

## 4. Erreur « unauthorized » en créant une marque ?

Avec `BILLING_ENFORCE_ACTIVE=true`, un compte en `manual_pending` **ne peut pas** créer de marque.

- **Option A (dev)** : dans `backend/.env` → `BILLING_ENFORCE_ACTIVE=false`, puis `php artisan config:clear`
- **Option B (prod)** : super-admin → `/admin/subscriptions` → **Activate** ton organisation

## 5. Parcours publication

1. Admin : activer l’abonnement (`/admin`) si `BILLING_ENFORCE_ACTIVE=true`
2. Lier Meta : `/social-accounts` (quand Meta le permet)
3. Créer contenu → approbation
4. **Instagram** : URL image publique HTTPS dans *Media image URL*
5. **Publish now** ou planifier + scheduler

## 6. Tester Groq (Windows / partout)

```bash
cd backend
php artisan config:clear
php artisan ai:smoke
```

Tu dois voir `HTTP status: 200` et `AI smoke test passed.`  
Si `401` / `403` : clé ou modèle invalide dans `.env`.

## 7. Meta minimum

```env
META_APP_ID=
META_APP_SECRET=
META_REDIRECT_URI=http://127.0.0.1:8000/api/meta/oauth/callback
```

Pour publier, ajoute les scopes publish (voir commentaire dans `.env.example`) et reconnecte les comptes.

## 8. Login `500` sur `/api/auth/login`

1. Ouvre **http://127.0.0.1:8000/api/health** — si `degraded`, suis le `hint`.
2. Backend :

```bash
cd backend
php artisan key:generate
touch database/database.sqlite   # si DB_CONNECTION=sqlite
php artisan migrate
```

3. Regarde la réponse JSON du login (après le correctif récent) ou `storage/logs/laravel.log` pour l’erreur exacte (table manquante, `APP_KEY`, etc.).
4. Frontend : `NEXT_PUBLIC_API_URL=http://127.0.0.1:8000` (sans slash final).