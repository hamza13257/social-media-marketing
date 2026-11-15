# MVP — SocialPilot Marketing

Périmètre actuel du produit (usage personnel puis petites équipes).

## Fonctionnalités MVP (incluses)

| Domaine | Fonctionnalités |
|--------|------------------|
| **Comptes** | Inscription, connexion, organisations multi-tenant, rôles owner / admin / editor |
| **Marques** | CRUD marques, archivage, quotas selon le plan |
| **Social** | OAuth Meta, liaison comptes Facebook / Instagram à une marque |
| **Contenu** | Studio, statuts (brouillon → approbation), calendrier (liste) |
| **IA** | Génération de légendes via API compatible OpenAI (**Groq par défaut**) |
| **Publication** | Publication immédiate ou planifiée, logs, texte Facebook, Instagram avec URL image publique |
| **Abonnements** | Plans Starter / Pro / Agency, paiement **manuel**, activation **manuelle** par super-admin |
| **Facturation** | Page `/billing` : choix de plan, statut `manual_pending` jusqu’à activation |
| **Admin plateforme** | `/admin` : tableau de bord, tenants, file d’attente paiements, gestion super-admins |
| **Paiement (config)** | `PAYMENT_GATEWAY=manual` ou `stripe` (stub Stripe, à brancher) |

## Hors MVP (prochaines étapes)

- Stripe Checkout + webhooks complets
- Bibliothèque média / S3, templates visuels
- Analytics Meta, carrousels / reels
- Invitations équipe par e-mail
- Quotas stricts sur comptes sociaux et générations IA (partiellement prévus via JSON `limits`)

## APIs à fournir (`.env` backend)

| Service | Variables |
|---------|-----------|
| **Groq** | `AI_API_KEY`, `AI_BASE_URL=https://api.groq.com/openai/v1`, `AI_MODEL=llama-3.3-70b-versatile` |
| **Meta** | `META_APP_ID`, `META_APP_SECRET`, `META_REDIRECT_URI`, `FRONTEND_URL` |
| **Facturation** | `PAYMENT_GATEWAY=manual`, `MANUAL_PAYMENT_INSTRUCTIONS`, optionnel `STRIPE_*` |
| **Développement** | `BILLING_ENFORCE_ACTIVE=true` (mettre `false` pour tester sans activation) |

## Premier super-admin

Après migration et seed des plans :

```bash
cd backend
php artisan migrate
php artisan db:seed --class=SubscriptionPlanSeeder
php artisan tinker --execute="\App\Models\User::where('email','VOTRE_EMAIL')->update(['is_super_admin'=>true]);"
```

Ou via l’UI Admin une fois un premier compte promu manuellement en base.