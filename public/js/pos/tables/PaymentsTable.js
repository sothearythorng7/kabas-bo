class PaymentsTable extends Table {
    constructor() {
        super("payments", {
            id: { type: "number", required: true },
            name: { type: "string", required: true },
            code: { type: "string", required: true }
        });
    }
}
