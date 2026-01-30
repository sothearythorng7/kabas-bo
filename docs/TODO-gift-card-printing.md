# TODO: Impression des codes de cartes cadeaux sur les tickets

> Document créé le 2026-01-19 pour reprendre le travail sur l'impression des codes de cartes cadeaux.

## Contexte

Quand une carte cadeau est vendue sur le POS, le code généré doit être imprimé sur le ticket de caisse pour que le client puisse l'utiliser.

## Ce qui a été fait

### 1. Génération du code côté client (POS)

**Fichier:** `public/js/pos/app.js`

- Ajout de la fonction `generateGiftCardCode()` (lignes 30-41) qui génère des codes au format `GIFT-XXXX-XXXX-XXXX`
- Modification de `addProductToCart()` (lignes 804-821) pour créer une ligne par gift card avec un code unique stocké dans `generated_code`
- Le champ `generated_code` est inclus dans le payload de sync (ligne 282)

### 2. Backend accepte le code fourni

**Fichier:** `app/Http/Controllers/POS/SyncController.php`

- Ajout de la validation pour `generated_code` (ligne 125)
- Modification de la création de GiftCardCode (lignes 232-264) pour utiliser le code fourni par le client si disponible

### 3. Données préparées pour l'impression

**Fichier:** `resources/views/pos/screens/dashboard.blade.php`

- Modification de `formatTicketData()` (lignes 231, 261-268, 341-344) pour collecter les codes des gift cards
- Les données sont ajoutées au ticket dans un champ `gift_cards`

## Ce qui reste à faire

### Service d'impression externe

Le service d'impression est externe (probablement un serveur Python/Node sur le réseau local qui communique avec l'imprimante thermique).

**URL de l'API:** Défini dans `PRINTER_CONFIG.url` (voir `resources/views/pos/index.blade.php`)

**Endpoint:** `POST /print`

**Format actuel des données envoyées:**
```javascript
{
    header: {
        title: "Adresse du magasin",
        subtitle: "Phone number: ..."
    },
    items: [
        { label: "Product Name", qty: 1, unit_price: "10.00", discount: "0.00", amount: "10.00" }
    ],
    subtotal: "10.00",
    total: "10.00",
    tax: "0.00",
    payment_method: "CASH",
    ticket_number: "TKT-20260119-001",
    date: "19/01/2026 15:30",
    footer: "Merci de votre visite!",
    // NOUVEAU CHAMP À GÉRER:
    gift_cards: [
        { name: "Gift Card $50", code: "GIFT-AB12-CD34-EF56", amount: "50.00" }
    ]
}
```

### Modifications à faire sur le service d'impression

1. **Détecter le champ `gift_cards`** dans les données reçues
2. **Afficher une section spéciale** sur le ticket après les totaux, par exemple:

```
================================
      GIFT CARD CODE(S)
================================
Gift Card $50
Code: GIFT-AB12-CD34-EF56
Value: $50.00
--------------------------------
(Keep this code for redemption)
================================
```

3. **Format suggéré pour l'impression:**
   - Centrer le titre "GIFT CARD CODE(S)"
   - Afficher le nom de la carte cadeau
   - Afficher le code en gros caractères ou avec espacement pour lisibilité
   - Afficher la valeur
   - Ajouter un message pour le client

## Référence: Format des vouchers (déjà implémenté)

Le service d'impression gère déjà les vouchers d'échange. Voir `formatVoucherTicketData()` dans `dashboard.blade.php` (lignes 349-414).

Format des vouchers:
```javascript
{
    // ... header, items ...
    voucher: {
        title: "STORE CREDIT VOUCHER",
        code: "ABC DEF GHI",
        amount: "25.00",
        expires: "19/04/2026"
    },
    // ... footer ...
}
```

Le format des gift cards pourrait s'inspirer de celui des vouchers.

## Tests à effectuer

1. Ajouter une carte cadeau au panier sur le POS
2. Vérifier que le code est généré (visible dans la console: `item.generated_code`)
3. Valider la vente
4. Vérifier que le ticket contient le champ `gift_cards` (console network)
5. Vérifier que le code est bien enregistré en base après la sync
6. Vérifier que le code imprimé correspond à celui en base

## Fichiers concernés

| Fichier | Statut | Description |
|---------|--------|-------------|
| `public/js/pos/app.js` | ✅ Fait | Génération code + payload sync |
| `app/Http/Controllers/POS/SyncController.php` | ✅ Fait | Accepte code fourni |
| `resources/views/pos/screens/dashboard.blade.php` | ✅ Fait | Prépare données impression |
| Service d'impression externe | ❌ À faire | Afficher les codes sur le ticket |

## Notes additionnelles

- Les caractères ambigus (0, O, 1, I) sont exclus de la génération de code pour éviter la confusion
- Chaque carte cadeau a sa propre ligne dans le panier (pas de quantité > 1 pour les gift cards)
- Le code est généré au moment de l'ajout au panier, pas à la validation
- Si le client annule la vente, le code n'est jamais envoyé au serveur
