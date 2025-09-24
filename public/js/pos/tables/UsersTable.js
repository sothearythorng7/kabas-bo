class UsersTable extends Table {
    constructor() {
        super("users", {
            id: { type: "number", required: true },
            name: { type: "string", required: true },
            pin_code: { type: "string", required: true },
            store_id: { type: "number", required: true }
        });
    }
}
