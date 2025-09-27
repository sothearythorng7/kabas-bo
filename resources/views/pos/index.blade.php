<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- jQuery & Bootstrap Bundle -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">


    <!-- CSS spécifique POS -->
    <link href="{{ asset('css/pos/main.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>
    <div class="container py-4">
        <!-- Conteneur principal -->
        <div id="pos-container" class="mt-4">

            <!-- Écrans -->
            @include('pos.screens.dashboard')
            @include('pos.screens.sales')
            @include('pos.screens.products')
            @include('pos.screens.login')
            @include('pos.screens.shift-start')
            @include('pos.screens.shift-end')
            @include('pos.screens.journal')
            @include('pos.screens.sale-detail')
        </div>
    </div>

    <!-- Core DB -->
    <script src="{{ asset('js/pos/core/Table.js') }}"></script>
    <script src="{{ asset('js/pos/core/Database.js') }}"></script>

    <!-- Tables -->
    <script src="{{ asset('js/pos/tables/UsersTable.js') }}"></script>
    <script src="{{ asset('js/pos/tables/CatalogTable.js') }}"></script>
    <script src="{{ asset('js/pos/tables/PaymentsTable.js') }}"></script>

    <!-- App -->
    <script src="{{ asset('js/pos/app.js') }}"></script>

    <!-- Scripts spécifiques poussés par chaque écran -->
    @stack('scripts')

    <!-- Modal synchronisation -->
    <div class="modal fade" id="syncModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <div class="modal-body">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p>Synchronisation en cours...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    window.i18n = {
        yes: @json(__('messages.yes')),
        no: @json(__('messages.no')),
        running: @json(__('messages.en_cours')),
        No_global_discount: @json(__('messages.No_global_discount')),
    };
    </script>
</body>
</html>
