@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.shipping.rates_title') }}</h1>
    <p class="text-muted">{{ __('messages.shipping.rates_subtitle') }}</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    @if($activeCountries->isEmpty())
        <div class="alert alert-warning">
            {{ __('messages.shipping.no_active_countries') }}
            <a href="{{ route('shipping-countries.index') }}">{{ __('messages.shipping.countries_title') }}</a>
        </div>
    @elseif($carriers->isEmpty())
        <div class="alert alert-warning">
            {{ __('messages.shipping.no_carriers') }}
            <a href="{{ route('shipping-carriers.index') }}">{{ __('messages.shipping.manage_carriers') }}</a>
        </div>
    @else
        <form method="GET" action="{{ route('shipping-rates.index') }}" class="mb-4">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">{{ __('messages.shipping.select_country') }}</label>
                    <select name="country_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- {{ __('messages.shipping.select_country') }} --</option>
                        @foreach($activeCountries as $country)
                            <option value="{{ $country->id }}" {{ $selectedCountry && $selectedCountry->id == $country->id ? 'selected' : '' }}>
                                {{ $country->code }} - {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">{{ __('messages.shipping.select_carrier') }}</label>
                    <select name="carrier_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- {{ __('messages.shipping.select_carrier') }} --</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}" {{ $selectedCarrier && $selectedCarrier->id == $carrier->id ? 'selected' : '' }}>
                                {{ $carrier->name }}{{ !$carrier->is_active ? ' (' . __('messages.shipping.inactive') . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if($selectedCountry && $selectedCarrier)
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('messages.shipping.rates_for', ['country' => $selectedCountry->name]) }} — {{ $selectedCarrier->name }}</h5>
                    <div>
                        <button type="button" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#duplicateRatesModal">
                            <i class="bi bi-copy"></i> {{ __('messages.shipping.duplicate_from') }}
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addRateModal">
                            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.shipping.add_rate') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if($rates->isEmpty())
                        <p class="text-muted">{{ __('messages.shipping.no_rates') }}</p>
                    @else
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.shipping.weight_from') }}</th>
                                    <th>{{ __('messages.shipping.weight_to') }}</th>
                                    <th>{{ __('messages.shipping.price_usd') }}</th>
                                    <th>{{ __('messages.shipping.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rates as $rate)
                                    <tr>
                                        <td>{{ $rate->weight_from }} g</td>
                                        <td>{{ $rate->weight_to }} g</td>
                                        <td>${{ number_format($rate->price, 2) }}</td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editRateModal{{ $rate->id }}">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            <form action="{{ route('shipping-rates.destroy', $rate) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.shipping.confirm_delete_rate') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    {{-- Edit Modal --}}
                                    <div class="modal fade" id="editRateModal{{ $rate->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('shipping-rates.update', $rate) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ __('messages.shipping.edit_rate') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.shipping.weight_from') }}</label>
                                                            <input type="number" step="1" min="0" name="weight_from" class="form-control" value="{{ $rate->weight_from }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.shipping.weight_to') }}</label>
                                                            <input type="number" step="1" min="0" name="weight_to" class="form-control" value="{{ $rate->weight_to }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.shipping.price_usd') }}</label>
                                                            <input type="number" step="0.00001" min="0" name="price" class="form-control" value="{{ $rate->price }}" required>
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

            {{-- Add Rate Modal --}}
            <div class="modal fade" id="addRateModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('shipping-rates.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="shipping_country_id" value="{{ $selectedCountry->id }}">
                            <input type="hidden" name="shipping_carrier_id" value="{{ $selectedCarrier->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('messages.shipping.add_rate') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('messages.shipping.weight_from') }}</label>
                                    <input type="number" step="1" min="0" name="weight_from" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('messages.shipping.weight_to') }}</label>
                                    <input type="number" step="1" min="0" name="weight_to" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('messages.shipping.price_usd') }}</label>
                                    <input type="number" step="0.00001" min="0" name="price" class="form-control" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                <button type="submit" class="btn btn-success">{{ __('messages.shipping.add_rate') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {{-- Duplicate Rates Modal --}}
            <div class="modal fade" id="duplicateRatesModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('shipping-rates.duplicate') }}" method="POST">
                            @csrf
                            <input type="hidden" name="target_country_id" value="{{ $selectedCountry->id }}">
                            <input type="hidden" name="shipping_carrier_id" value="{{ $selectedCarrier->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('messages.shipping.duplicate_rates') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">{{ __('messages.shipping.select_source_country') }}</label>
                                    <select name="source_country_id" class="form-select" required>
                                        <option value="">-- {{ __('messages.shipping.select_source_country') }} --</option>
                                        @foreach($activeCountries as $country)
                                            @if($country->id !== $selectedCountry->id)
                                                <option value="{{ $country->id }}">{{ $country->code }} - {{ $country->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('messages.shipping.duplicate_from') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
