class CatalogTable extends Table {
    constructor() {
        super("catalog", {
            id: { type: "number", required: true },
            ean: { type: "string", required: true },
            name: { type: "object", required: true },          
            description: { type: "object", required: false }, 
            slugs: { type: "object", required: false },       
            price: { type: "string", required: true },
            price_btob: { type: "string", required: false },
            brand: { type: "object", required: false },
            categories: { type: "array", required: false }, // nouvelles catégories
            photos: { type: "array", required: false },     // nouvelles photos
            total_stock: { type: "string", required: false },
            store_id: { type: "number", required: true }      
        });
    }

    // Recherche "OR" sur ean ou name.en
    search(query) {
        query = query.trim().toLowerCase();
        return this.data.filter(row => {
            const eanMatch = row.ean && row.ean.toLowerCase().includes(query);
            const nameMatch = row.name?.en && row.name.en.toLowerCase().includes(query);
            return eanMatch || nameMatch;
        });
    }

    // Méthode pour insérer un produit complet avec catégories et photos
    insertProduct(product) {
        this.insert({
            id: product.id,
            ean: product.ean,
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
