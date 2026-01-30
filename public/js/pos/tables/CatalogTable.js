class CatalogTable extends Table {
    constructor() {
        super("catalog", {
            id: { type: "string", required: true }, // string pour supporter "giftbox_1", "giftcard_1"
            original_id: { type: "number", required: false }, // ID original pour gift_box/gift_card
            type: { type: "string", required: false }, // "product", "gift_box", "gift_card"
            ean: { type: "string", required: false }, // nullable pour gift_cards
            barcodes: { type: "array", required: false }, // tous les codes-barres
            name: { type: "object", required: true },
            description: { type: "object", required: false },
            slugs: { type: "object", required: false },
            price: { type: "string", required: true },
            price_btob: { type: "string", required: false },
            brand: { type: "object", required: false },
            categories: { type: "array", required: false }, // catégories
            photos: { type: "array", required: false },     // photos
            total_stock: { type: "string", required: false },
            store_id: { type: "number", required: true }
        });
    }

    // Recherche sur ean, barcodes (exact) ou name (partiel)
    search(query) {
        query = query.trim().toLowerCase();

        // Si la requête ressemble à un code-barre (numérique ou alphanumérique avec tirets)
        const isBarcodeQuery = /^[\d\-a-zA-Z]{4,20}$/.test(query);

        return this.data.filter(row => {
            if (isBarcodeQuery) {
                // Recherche exacte dans l'EAN principal
                if (row.ean && row.ean.toLowerCase() === query) {
                    return true;
                }

                // Recherche exacte dans tous les barcodes
                if (row.barcodes && Array.isArray(row.barcodes)) {
                    const barcodeMatch = row.barcodes.some(bc => {
                        const barcode = typeof bc === 'object' ? bc.barcode : bc;
                        return barcode && barcode.toLowerCase() === query;
                    });
                    if (barcodeMatch) return true;
                }

                // Recherche partielle pour les codes plus longs
                const eanPartial = row.ean && row.ean.toLowerCase().includes(query);
                if (eanPartial) return true;

                if (row.barcodes && Array.isArray(row.barcodes)) {
                    return row.barcodes.some(bc => {
                        const barcode = typeof bc === 'object' ? bc.barcode : bc;
                        return barcode && barcode.toLowerCase().includes(query);
                    });
                }

                return false;
            } else {
                // Recherche partielle pour les noms
                const eanMatch = row.ean && row.ean.toLowerCase().includes(query);
                const nameMatchEn = row.name?.en && row.name.en.toLowerCase().includes(query);
                const nameMatchFr = row.name?.fr && row.name.fr.toLowerCase().includes(query);
                return eanMatch || nameMatchEn || nameMatchFr;
            }
        });
    }

    // Méthode pour insérer un produit/coffret/carte cadeau avec catégories et photos
    insertProduct(product) {
        this.insert({
            id: String(product.id), // Convertir en string pour uniformité
            original_id: product.original_id || null,
            type: product.type || 'product',
            ean: product.ean || null,
            barcodes: product.barcodes || [],
            name: product.name,
            description: product.description || {},
            slugs: product.slugs || {},
            price: product.price,
            price_btob: product.price_btob || null,
            brand: product.brand || {},
            categories: product.categories || [],
            photos: product.photos || [],
            total_stock: product.total_stock || "0",
            store_id: product.store_id || 1
        });
    }
}
