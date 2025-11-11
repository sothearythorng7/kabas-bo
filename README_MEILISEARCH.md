# Meilisearch - Guide de maintenance

## Installation effectuée

Meilisearch v1.24.0 a été installé dans `/var/www/kabas/meilisearch/`

- **Binaire** : `/var/www/kabas/meilisearch/meilisearch`
- **Données** : `/var/www/kabas/meilisearch/data/`
- **Script démarrage** : `/var/www/kabas/meilisearch/start.sh`
- **Log** : `/var/www/kabas/meilisearch/meilisearch.log`

## Configuration

- **Host** : `http://127.0.0.1:7700`
- **Master Key** : `kabasSecureMasterKey2025ChangeThis`
- **Index** : `products` (731 produits indexés)

## Démarrage manuel

Si Meilisearch n'est pas lancé :

```bash
cd /var/www/kabas/meilisearch
./start.sh &
```

## Vérifier le statut

```bash
curl http://127.0.0.1:7700/health
# Devrait retourner: {"status":"available"}
```

Vérifier les stats de l'index :

```bash
curl -X GET 'http://127.0.0.1:7700/indexes/products/stats' \
  -H "Authorization: Bearer kabasSecureMasterKey2025ChangeThis"
```

## Démarrage automatique au boot

### Option 1 : Service systemd (nécessite sudo)

```bash
sudo cp /var/www/kabas/meilisearch/meilisearch.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable meilisearch
sudo systemctl start meilisearch
```

Vérifier le statut :

```bash
sudo systemctl status meilisearch
```

### Option 2 : Cron job

```bash
crontab -e
```

Ajouter :

```
@reboot cd /var/www/kabas/meilisearch && ./start.sh > /dev/null 2>&1 &
```

## Maintenance

### Ré-indexer tous les produits

```bash
php artisan scout:import "App\Models\Product"
```

### Vider l'index

```bash
php artisan scout:flush "App\Models\Product"
```

### Supprimer et recréer l'index

```bash
curl -X DELETE 'http://127.0.0.1:7700/indexes/products' \
  -H "Authorization: Bearer kabasSecureMasterKey2025ChangeThis"

php artisan scout:import "App\Models\Product"
```

## Fonctionnement

- Lorsqu'un produit est créé/modifié, il est **automatiquement** indexé dans Meilisearch
- La recherche sur `/products` utilise Meilisearch si une requête `?q=...` est présente
- Sans recherche, la page utilise une requête SQL classique

## Capacités de recherche

- ✅ Recherche multilingue (FR/EN)
- ✅ Tolérance aux fautes de frappe
- ✅ Recherche partielle
- ✅ Insensible à la casse
- ✅ Recherche dans : nom produit, description, marque, catégories, EAN

## Exemples de recherches

```bash
# Via Tinker
php artisan tinker

Product::search('chocolate')->take(5)->get();
Product::search('CRAFT')->where('is_active', true)->get();
Product::search('choclate')->get(); // Trouve "chocolate" malgré la faute
```

## Troubleshooting

### Meilisearch ne démarre pas

Vérifier les logs :

```bash
tail -f /var/www/kabas/meilisearch/meilisearch.log
```

### Port 7700 déjà utilisé

Trouver le processus :

```bash
lsof -i :7700
# ou
ps aux | grep meilisearch
```

Tuer le processus :

```bash
kill <PID>
```

### Recherche ne fonctionne pas

Vérifier que l'index existe :

```bash
curl http://127.0.0.1:7700/indexes \
  -H "Authorization: Bearer kabasSecureMasterKey2025ChangeThis"
```

Ré-indexer :

```bash
php artisan scout:import "App\Models\Product"
```

## Documentation officielle

- Meilisearch : https://www.meilisearch.com/docs
- Laravel Scout : https://laravel.com/docs/11.x/scout
