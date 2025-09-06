@extends('layouts.app')

@section('content')
<h1 class="mt-4">Dashboard</h1>

<!-- Exemple de contenu responsive avec Bootstrap -->
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">Carte 1</div>
            <div class="card-body">
                <input type="text" id="barcodeInput" autofocus>
                <div class="mb-3">
                    <h5>Dernier code scanné :</h5>
                    <p id="lastBarcode" class="fw-bold text-primary">-</p>
                </div>
                <div class="mb-3">
                    <h5>Historique des scans :</h5>
                    <ul id="barcodeList" class="list-group barcode-list"></ul>
                </div>
                <button id="clearHistory" class="btn btn-secondary">Vider l'historique</button>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        const input = document.getElementById('barcodeInput');
        const lastBarcodeDisplay = document.getElementById('lastBarcode');
        const barcodeList = document.getElementById('barcodeList');
        const clearHistoryBtn = document.getElementById('clearHistory');
        let barcode = ''; // Buffer pour le code-barres
        let lastKeyTime = Date.now(); // Pour détecter les scans rapides

        // Configurer le CSRF pour les requêtes AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Capturer les frappes du scanner
        input.addEventListener('keydown', (event) => {
            const currentTime = Date.now();
            // Réinitialiser si pause > 50ms (pas un scan)
            if (currentTime - lastKeyTime > 50) {
                barcode = '';
            }
            lastKeyTime = currentTime;

            if (event.key === 'Enter') { // Enter détecté : fin du scan
                if (barcode.length > 0) {
                    // Afficher le code scanné
                    lastBarcodeDisplay.textContent = barcode;
                    // Ajouter à l'historique
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    li.textContent = barcode + ' (' + new Date().toLocaleTimeString() + ')';
                    barcodeList.prepend(li);


                    barcode = ''; // Réinitialiser pour le prochain scan
                }
                event.preventDefault(); // Empêcher le comportement par défaut d'Enter
            } else {
                barcode += event.key; // Ajouter le caractère
            }
        });

        // Garder le focus sur l'input si l'utilisateur clique ailleurs
        document.addEventListener('click', () => {
            input.focus();
        });

        // Vider l'historique
        clearHistoryBtn.addEventListener('click', () => {
            barcodeList.innerHTML = '';
            lastBarcodeDisplay.textContent = '-';
        });
    });
</script>
@endsection
