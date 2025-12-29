@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h1 class="crud_title">{{ __('messages.store_dashboard.title') }} - {{ $site->name }}</h1>

    <!-- Onglets Bootstrap qui pointent vers les pages correspondantes -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.dashboard.index', $site) }}">{{ __('messages.store_nav.general_info') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.journals.index', $site) }}">{{ __('messages.store_nav.journals') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.payments.index', $site) }}">{{ __('messages.store_nav.supplier_payments') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expenses.index', $site) }}">{{ __('messages.store_nav.expenses') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expense-categories.index', $site) }}">{{ __('messages.store_nav.categories') }}</a>
        </li>
    </ul>

    <!-- Contenu du dashboard général -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.store_dashboard.revenue_this_month') }}</h5>
                    <p class="card-text fs-3">{{ number_format($revenue, 2) }} $</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.store_dashboard.expenses_this_month') }}</h5>
                    <p class="card-text fs-3">{{ number_format($totalExpenses, 2) }} $</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.store_dashboard.net_balance') }}</h5>
                    <p class="card-text fs-3">{{ number_format($net, 2) }} $</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
