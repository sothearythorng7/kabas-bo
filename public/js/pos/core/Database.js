class Database {
    constructor() {
        this.tables = {};
    }

    register(tableInstance) {
        this.tables[tableInstance.tableName] = tableInstance;
    }

    table(name) {
        return this.tables[name];
    }

    clearAll() {
        Object.values(this.tables).forEach(t => t.clear());
    }
}
