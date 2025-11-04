@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.inventory.title') }}</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-download"></i> {{ __('messages.inventory.download_section_title') }}</h5>
                </div>
                <div class="card-body">
                    <p>{{ __('messages.inventory.download_description') }}</p>
                    <form action="{{ route('inventory.export') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="export_store_id" class="form-label">{{ __('messages.inventory.select_store') }} *</label>
                            <select name="store_id" id="export_store_id" class="form-select" required>
                                <option value="">-- {{ __('messages.inventory.choose_store') }} --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="export_brand_id" class="form-label">{{ __('messages.inventory.filter_by_brand') }}</label>
                            <select name="brand_id" id="export_brand_id" class="form-select">
                                <option value="all">-- {{ __('messages.inventory.all_brands') }} --</option>
                                <option value="none">-- {{ __('messages.inventory.no_brand') }} --</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-file-earmark-excel"></i> {{ __('messages.inventory.download_excel') }}
                        </button>
                    </form>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>{{ __('messages.inventory.note_protection') }}</strong> {{ __('messages.inventory.note_protection_text') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-upload"></i> {{ __('messages.inventory.upload_section_title') }}</h5>
                </div>
                <div class="card-body">
                    <p>{{ __('messages.inventory.upload_description') }}</p>
                    <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="import_store_id" class="form-label">{{ __('messages.inventory.select_store') }} *</label>
                            <select name="store_id" id="import_store_id" class="form-select" required>
                                <option value="">-- {{ __('messages.inventory.choose_store') }} --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="import_brand_id" class="form-label">{{ __('messages.inventory.filter_by_brand') }}</label>
                            <select name="brand_id" id="import_brand_id" class="form-select">
                                <option value="all">-- {{ __('messages.inventory.all_brands') }} --</option>
                                <option value="none">-- {{ __('messages.inventory.no_brand') }} --</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.inventory.brand_filter_help') }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="inventory_file" class="form-label">{{ __('messages.inventory.select_file') }} *</label>
                            <input type="file" name="inventory_file" id="inventory_file" class="form-control" accept=".xlsx,.xls" required>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-cloud-upload"></i> {{ __('messages.inventory.import_analyze') }}
                        </button>
                    </form>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>{{ __('messages.inventory.warning_same_store') }}</strong> {{ __('messages.inventory.warning_same_store_text') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> {{ __('messages.inventory.procedure_title') }}</h5>
        </div>
        <div class="card-body">
            <ol>
                <li><strong>{{ __('messages.inventory.procedure_step1') }}</strong> {{ __('messages.inventory.procedure_step1_text') }}</li>
                <li><strong>{{ __('messages.inventory.procedure_step2') }}</strong> {{ __('messages.inventory.procedure_step2_text') }}</li>
                <li><strong>{{ __('messages.inventory.procedure_step3') }}</strong> {{ __('messages.inventory.procedure_step3_text') }}</li>
                <li><strong>{{ __('messages.inventory.procedure_step4') }}</strong> {{ __('messages.inventory.procedure_step4_text') }}</li>
                <li><strong>{{ __('messages.inventory.procedure_step5') }}</strong> {{ __('messages.inventory.procedure_step5_text') }}</li>
            </ol>
            <div class="alert alert-primary">
                <i class="bi bi-lightbulb"></i>
                <strong>{{ __('messages.inventory.tip') }}</strong> {{ __('messages.inventory.tip_text') }}
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Succès</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto">Erreur</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('error') }}
            </div>
        </div>
    </div>
@endif

@if(session('info'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-info text-white">
                <strong class="me-auto">Information</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('info') }}
            </div>
        </div>
    </div>
@endif
@endsection
