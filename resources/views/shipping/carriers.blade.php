@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.shipping.carriers_title') }}</h1>
    <p class="text-muted">{{ __('messages.shipping.carriers_subtitle') }}</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('messages.shipping.carriers_title') }}</h5>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCarrierModal">
                <i class="bi bi-plus-circle-fill"></i> {{ __('messages.shipping.add_carrier') }}
            </button>
        </div>
        <div class="card-body">
            @if($carriers->isEmpty())
                <p class="text-muted">{{ __('messages.shipping.no_carriers') }}</p>
            @else
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('messages.shipping.carrier_name') }}</th>
                            <th>{{ __('messages.shipping.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($carriers as $carrier)
                            <tr>
                                <td>{{ $carrier->name }}</td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCarrierModal{{ $carrier->id }}">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <form action="{{ route('shipping-carriers.destroy', $carrier) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.shipping.confirm_delete_carrier') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Edit Modal --}}
                            <div class="modal fade" id="editCarrierModal{{ $carrier->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('shipping-carriers.update', $carrier) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('messages.shipping.edit_carrier') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.shipping.carrier_name') }}</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $carrier->name }}" maxlength="100" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Add Carrier Modal --}}
    <div class="modal fade" id="addCarrierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('shipping-carriers.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('messages.shipping.add_carrier') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.shipping.carrier_name') }}</label>
                            <input type="text" name="name" class="form-control" maxlength="100" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('messages.shipping.add_carrier') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
