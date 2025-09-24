class Table {
    constructor(tableName, columns) {
        this.tableName = tableName;
        this.columns = columns;
        this.data = [];
    }

    validate(row) {
        const newRow = {};
        for (let col in this.columns) {
            if (!(col in row)) {
                if (this.columns[col].required) {
                    throw new Error(`Colonne manquante: ${col}`);
                } else {
                    newRow[col] = null;
                }
            } else {
                newRow[col] = row[col];
            }
        }
        return newRow;
    }

    insert(row) {
        this.data.push(this.validate(row));
    }

    insertMany(rows) {
        rows.forEach(r => this.insert(r));
    }

    findExact(criteria) {
        return this.data.filter(row =>
            Object.entries(criteria).every(([key, value]) => row[key] === value)
        );
    }

    findLike(criteria) {
        return this.data.filter(row =>
            Object.entries(criteria).some(([key, value]) => {
                if (typeof row[key] === "string") {
                    return row[key].toLowerCase().includes(value.toLowerCase());
                }
                return row[key] == value;
            })
        );
    }

    clear() {
        this.data = [];
    }
}
