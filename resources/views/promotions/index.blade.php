@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="crud_title mb-0">{{ __('messages.promotion.title') }}</h1>
        <a href="{{ route('promotions.create') }}" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> {{ __('messages.promotion.create') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('promotions.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="{{ __('messages.promotion.search_name') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('messages.promotion.all_statuses') }}</option>
                @foreach(['draft','active','paused','expired','archived'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('messages.promotion.status_' . $s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="mode" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('messages.promotion.all_modes') }}</option>
                <option value="automatic" @selected(request('mode') === 'automatic')>{{ __('messages.promotion.mode_automatic') }}</option>
                <option value="code_required" @selected(request('mode') === 'code_required')>{{ __('messages.promotion.mode_code_required') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['search','status','mode']))
                <a href="{{ route('promotions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            @endif
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>{{ __('messages.promotion.name') }}</th>
                    <th>{{ __('messages.promotion.status') }}</th>
                    <th>{{ __('messages.promotion.mode') }}</th>
                    <th class="text-center">{{ __('messages.promotion.priority') }}</th>
                    <th class="text-center">{{ __('messages.promotion.conditions') }} / {{ __('messages.promotion.actions_label') }} / {{ __('messages.promotion.codes') }}</th>
                    <th class="text-center">{{ __('messages.promotion.usage_count') }}</th>
                    <th>{{ __('messages.promotion.window') }}</th>
                    <th style="width: 140px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>
                            <strong>{{ $rule->name ?: '#'.$rule->id }}</strong><br>
                            <small class="text-muted">#{{ $rule->id }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $rule->status === 'active' ? 'success' : ($rule->status === 'draft' ? 'secondary' : 'warning') }}">
                                {{ __('messages.promotion.status_' . $rule->status) }}
                            </span>
                        </td>
                        <td>{{ __('messages.promotion.mode_' . $rule->activation_mode) }}</td>
                        <td class="text-center">{{ $rule->priority }}</td>
                        <td class="text-center">{{ $rule->conditions_count }} / {{ $rule->actions_count }} / {{ $rule->codes_count }}</td>
                        <td class="text-center">{{ $rule->usage_count }}{{ $rule->max_uses_total ? ' / '.$rule->max_uses_total : '' }}</td>
                        <td>
                            @if($rule->starts_at || $rule->ends_at)
                                <small>
                                    {{ optional($rule->starts_at)->format('Y-m-d') ?: '—' }} →
                                    {{ optional($rule->ends_at)->format('Y-m-d') ?: '∞' }}
                                </small>
                            @else
                                <small class="text-muted">{{ __('messages.promotion.permanent') }}</small>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('promotions.edit', $rule) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('promotions.destroy', $rule) }}" class="d-inline" onsubmit="return confirm('{{ __('messages.promotion.delete_confirm') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('messages.promotion.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $rules->links() }}
</div>
@endsection
