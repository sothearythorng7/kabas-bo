@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.inventory.confirm_title') }}</h1>

    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>{{ __('messages.inventory.warning_same_store') }}</strong> {{ __('messages.inventory.confirm_warning') }} <strong>{{ $store->name }}</strong>.
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('messages.inventory.confirm_adjustments_count', ['count' => count($updates)]) }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('messages.inventory.confirm_table_id') }}</th>
                            <th>{{ __('messages.inventory.confirm_table_product') }}</th>
                            <th class="text-center">{{ __('messages.inventory.confirm_table_theoretical') }}</th>
                            <th class="text-center">{{ __('messages.inventory.confirm_table_real') }}</th>
                            <th class="text-center">{{ __('messages.inventory.confirm_table_difference') }}</th>
                            <th class="text-center">{{ __('messages.inventory.confirm_table_action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($updates as $update)
                            <tr>
                                <td>{{ $update['product_id'] }}</td>
                                <td>{{ $update['product_name'] }}</td>
                                <td class="text-center">{{ $update['theoretical'] }}</td>
                                <td class="text-center"><strong>{{ $update['real'] }}</strong></td>
                                <td class="text-center">
                                    @if($update['difference'] > 0)
                                        <span class="badge bg-success">+{{ $update['difference'] }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $update['difference'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($update['difference'] > 0)
                                        <span class="text-success"><i class="bi bi-arrow-up-circle"></i> {{ __('messages.inventory.confirm_action_add') }}</span>
                                    @else
                                        <span class="text-danger"><i class="bi bi-arrow-down-circle"></i> {{ __('messages.inventory.confirm_action_remove') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3">
        <form action="{{ route('inventory.apply') }}" method="POST" onsubmit="return confirm('{{ __('messages.inventory.confirm_prompt') }}');">
            @csrf
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle"></i> {{ __('messages.inventory.confirm_button') }}
            </button>
        </form>

        <form action="{{ route('inventory.cancel') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-lg">
                <i class="bi bi-x-circle"></i> {{ __('messages.inventory.confirm_cancel') }}
            </button>
        </form>
    </div>

    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle"></i>
        <strong>{{ __('messages.inventory.confirm_info_title') }}</strong>
        <ul class="mb-0 mt-2">
            <li>{{ __('messages.inventory.confirm_info_add') }}</li>
            <li>{{ __('messages.inventory.confirm_info_remove') }}</li>
            <li>{{ __('messages.inventory.confirm_info_irreversible') }}</li>
        </ul>
    </div>
</div>
@endsection
