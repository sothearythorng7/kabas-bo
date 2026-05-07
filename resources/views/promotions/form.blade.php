@extends('layouts.app')

@section('content')
@php
    $locale = app()->getLocale();
    $ruleName = old('name', $rule->name ?? '');
    $categoriesJson = $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values();
    $brandsJson = $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values();
    $productsJson = $topProducts->map(fn ($p) => ['id' => $p->id, 'name' => is_array($p->name) ? ($p->name[$locale] ?? ($p->name['en'] ?? reset($p->name))) : $p->name, 'ean' => $p->ean])->values();
@endphp

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="crud_title mb-0">
            {{ $rule->exists ? __('messages.promotion.edit_title') : __('messages.promotion.create_title') }}
        </h1>
        <a href="{{ route('promotions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.promotion.back_to_list') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $rule->exists ? route('promotions.update', $rule) : route('promotions.store') }}">
        @csrf
        @if($rule->exists) @method('PUT') @endif

        <div class="card mb-3">
            <div class="card-header">{{ __('messages.promotion.section_header') }}</div>
            <div class="card-body row g-3">
                <div class="col-md-12">
                    <label class="form-label">{{ __('messages.promotion.field.name') }} *</label>
                    <input type="text" name="name" class="form-control" value="{{ $ruleName }}" required placeholder="{{ __('messages.promotion.name_help') }}">
                    <small class="text-muted">{{ __('messages.promotion.name_internal_only') }}</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.promotion.field.status') }} *</label>
                    <select name="status" class="form-select" required>
                        @foreach(['draft','active','paused','expired','archived'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $rule->status) === $s)>{{ __('messages.promotion.status_' . $s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.mode') }} *</label>
                    <select name="activation_mode" class="form-select" required>
                        <option value="automatic" @selected(old('activation_mode', $rule->activation_mode) === 'automatic')>{{ __('messages.promotion.mode_automatic') }}</option>
                        <option value="code_required" @selected(old('activation_mode', $rule->activation_mode) === 'code_required')>{{ __('messages.promotion.mode_code_required') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.channel') }} *</label>
                    <select name="channel" class="form-select" required>
                        <option value="website" @selected(old('channel', $rule->channel) === 'website')>{{ __('messages.promotion.channel_website') }}</option>
                        <option value="pos" @selected(old('channel', $rule->channel) === 'pos')>{{ __('messages.promotion.channel_pos') }}</option>
                        <option value="both" @selected(old('channel', $rule->channel) === 'both')>{{ __('messages.promotion.channel_both') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.starts_at') }}</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', optional($rule->starts_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.ends_at') }}</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', optional($rule->ends_at)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.promotion.field.priority') }}</label>
                    <input type="number" name="priority" class="form-control" value="{{ old('priority', $rule->priority) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.stackable_group') }}</label>
                    <input type="text" name="stackable_group" class="form-control" value="{{ old('stackable_group', $rule->stackable_group) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.promotion.field.conditions_logic') }}</label>
                    <select name="conditions_logic" class="form-select">
                        <option value="all" @selected(old('conditions_logic', $rule->conditions_logic) === 'all')>{{ __('messages.promotion.logic_all') }}</option>
                        <option value="any" @selected(old('conditions_logic', $rule->conditions_logic) === 'any')>{{ __('messages.promotion.logic_any') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_exclusive" value="0">
                        <input class="form-check-input" type="checkbox" name="is_exclusive" id="is_exclusive" value="1" @checked(old('is_exclusive', $rule->is_exclusive))>
                        <label class="form-check-label" for="is_exclusive">{{ __('messages.promotion.field.is_exclusive') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">{{ __('messages.promotion.section_limits') }}</div>
            <div class="card-body row g-3">
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.max_uses_total') }}</label>
                    <input type="number" min="0" name="max_uses_total" class="form-control" value="{{ old('max_uses_total', $rule->max_uses_total) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.max_uses_per_customer') }}</label>
                    <input type="number" min="0" name="max_uses_per_customer" class="form-control" value="{{ old('max_uses_per_customer', $rule->max_uses_per_customer) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.promotion.field.max_budget') }}</label>
                    <input type="number" step="0.01" min="0" name="max_budget" class="form-control" value="{{ old('max_budget', $rule->max_budget) }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <small class="text-muted">{{ __('messages.promotion.budget_consumed') }}: ${{ number_format((float)$rule->budget_consumed, 2) }}</small>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('messages.promotion.section_conditions') }}</span>
                <button type="button" class="btn btn-sm btn-success" id="add-condition">
                    <i class="bi bi-plus-lg"></i> {{ __('messages.promotion.add_condition') }}
                </button>
            </div>
            <div class="card-body">
                <div id="conditions-list"></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ __('messages.promotion.section_actions') }}</span>
                <button type="button" class="btn btn-sm btn-success" id="add-action">
                    <i class="bi bi-plus-lg"></i> {{ __('messages.promotion.add_action') }}
                </button>
            </div>
            <div class="card-body">
                <div id="actions-list"></div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('promotions.index') }}" class="btn btn-outline-secondary">{{ __('messages.promotion.cancel') }}</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> {{ __('messages.promotion.save') }}
            </button>
        </div>
    </form>

    @if($rule->exists)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ __('messages.promotion.section_codes') }}</span>
            <small class="text-muted">{{ __('messages.promotion.codes_help') }}</small>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('promotions.codes.store', $rule) }}" class="row g-2 mb-3">
                @csrf
                <div class="col-md-3">
                    <input type="text" name="code" class="form-control" placeholder="{{ __('messages.promotion.field.code') }}" required>
                </div>
                <div class="col-md-2">
                    <input type="number" min="0" name="max_uses" class="form-control" placeholder="{{ __('messages.promotion.field.code_max_uses') }}">
                </div>
                <div class="col-md-2">
                    <input type="number" min="0" name="per_customer_limit" class="form-control" placeholder="{{ __('messages.promotion.field.code_per_customer') }}">
                </div>
                <div class="col-md-2">
                    <input type="datetime-local" name="starts_at" class="form-control" placeholder="Start">
                </div>
                <div class="col-md-2">
                    <input type="datetime-local" name="ends_at" class="form-control" placeholder="End">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-lg"></i></button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('messages.promotion.field.code') }}</th>
                            <th class="text-center">{{ __('messages.promotion.field.is_active') }}</th>
                            <th class="text-center">{{ __('messages.promotion.usage_count') }}</th>
                            <th>{{ __('messages.promotion.field.starts_at') }}</th>
                            <th>{{ __('messages.promotion.field.ends_at') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rule->codes as $code)
                            <tr>
                                <td><code>{{ $code->code }}</code></td>
                                <td class="text-center">
                                    @if($code->is_active)
                                        <span class="badge bg-success">{{ __('messages.promotion.active_yes') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('messages.promotion.active_no') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $code->usage_count }}{{ $code->max_uses ? ' / '.$code->max_uses : '' }}</td>
                                <td><small>{{ optional($code->starts_at)->format('Y-m-d H:i') ?: '—' }}</small></td>
                                <td><small>{{ optional($code->ends_at)->format('Y-m-d H:i') ?: '—' }}</small></td>
                                <td>
                                    <form method="POST" action="{{ route('promotions.codes.destroy', [$rule, $code]) }}" onsubmit="return confirm('{{ __('messages.promotion.delete_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">{{ __('messages.promotion.no_codes') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
(function() {
    const conditionCatalog = @json($conditionCatalog);
    const actionCatalog = @json($actionCatalog);
    const categories = @json($categoriesJson);
    const brands = @json($brandsJson);
    const products = @json($productsJson);
    const existingConditions = @json($rule->conditions->map->only(['type','operator','params'])->values());
    const existingActions = @json($rule->actions->map->only(['type','params'])->values());

    const condList = document.getElementById('conditions-list');
    const actList = document.getElementById('actions-list');

    function renderField(field, nameBase, value) {
        const label = `<label class="form-label small">${field.label || field.key}${field.required ? ' *' : ''}</label>`;
        const inputName = `${nameBase}[params][${field.key}]`;
        const v = value ?? field.default ?? '';
        let input = '';
        switch (field.type) {
            case 'decimal':
                input = `<input type="number" step="0.00001" name="${inputName}" class="form-control form-control-sm" value="${v === '' ? '' : v}">`;
                break;
            case 'integer':
                input = `<input type="number" step="1" name="${inputName}" class="form-control form-control-sm" value="${v === '' ? '' : v}">`;
                break;
            case 'category_multi': {
                const vals = Array.isArray(v) ? v.map(Number) : [];
                input = `<select multiple name="${inputName}[]" class="form-select form-select-sm" size="5">` +
                    categories.map(c => `<option value="${c.id}"${vals.includes(c.id) ? ' selected' : ''}>${c.name || ('Cat #'+c.id)}</option>`).join('') +
                    `</select>`;
                break;
            }
            case 'brand_multi': {
                const vals = Array.isArray(v) ? v.map(Number) : [];
                input = `<select multiple name="${inputName}[]" class="form-select form-select-sm" size="5">` +
                    brands.map(b => `<option value="${b.id}"${vals.includes(b.id) ? ' selected' : ''}>${b.name || ('Brand #'+b.id)}</option>`).join('') +
                    `</select>`;
                break;
            }
            case 'country_multi': {
                const vals = Array.isArray(v) ? v.join(',') : (v || '');
                input = `<input type="text" name="${inputName}" class="form-control form-control-sm" value="${vals}" placeholder="KH,FR,US">
                         <small class="text-muted">ISO-2 codes, comma-separated.</small>`;
                break;
            }
            case 'product': {
                input = `<select name="${inputName}" class="form-select form-select-sm">
                    <option value="">—</option>` +
                    products.map(p => `<option value="${p.id}"${Number(v) === p.id ? ' selected' : ''}>${p.name || p.ean || ('#'+p.id)}</option>`).join('') +
                    `</select>`;
                break;
            }
            default:
                input = `<input type="text" name="${inputName}" class="form-control form-control-sm" value="${v}">`;
        }
        return `<div class="col-md-4 mb-2">${label}${input}</div>`;
    }

    function renderRow(catalog, typeKey, baseName, params, operator, kind) {
        const typeEntry = catalog.find(t => t.key === typeKey) || catalog[0];
        const wrapper = document.createElement('div');
        wrapper.className = 'border rounded p-3 mb-2 position-relative';
        wrapper.innerHTML = `
            <button type="button" class="btn btn-sm btn-outline-danger position-absolute" style="top: 8px; right: 8px;" data-remove>
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">{{ __('messages.promotion.field.type') }}</label>
                    <select name="${baseName}[type]" class="form-select form-select-sm" data-type-select>
                        ${catalog.map(t => `<option value="${t.key}"${t.key === typeEntry.key ? ' selected' : ''}>${t.label}</option>`).join('')}
                    </select>
                </div>
                ${kind === 'condition' && typeEntry.supports_operator ? `
                <div class="col-md-2">
                    <label class="form-label small">{{ __('messages.promotion.field.operator') }}</label>
                    <select name="${baseName}[operator]" class="form-select form-select-sm">
                        ${typeEntry.operators.map(op => `<option value="${op}"${op === operator ? ' selected' : ''}>${op}</option>`).join('')}
                    </select>
                </div>` : ''}
                <div class="col-md-7 row g-2 fields-container"></div>
            </div>
        `;

        const fieldsContainer = wrapper.querySelector('.fields-container');
        typeEntry.fields.forEach(f => {
            const tmp = document.createElement('div');
            tmp.innerHTML = renderField(f, baseName, params ? params[f.key] : undefined);
            fieldsContainer.appendChild(tmp.firstElementChild);
        });

        wrapper.querySelector('[data-remove]').addEventListener('click', () => wrapper.remove());

        wrapper.querySelector('[data-type-select]').addEventListener('change', (e) => {
            // Re-render the whole row with new type
            const parent = wrapper.parentNode;
            const idx = Array.from(parent.children).indexOf(wrapper);
            const newBaseName = baseName.replace(/\[\d+\]/, '['+idx+']');
            const fresh = renderRow(catalog, e.target.value, newBaseName, null, null, kind);
            parent.replaceChild(fresh, wrapper);
        });

        return wrapper;
    }

    function currentBase(kind, idx) {
        return `${kind}s[${idx}]`;
    }

    function addCondition(typeKey, params, operator) {
        const idx = condList.children.length;
        condList.appendChild(renderRow(conditionCatalog, typeKey, currentBase('condition', idx), params, operator, 'condition'));
    }

    function addAction(typeKey, params) {
        const idx = actList.children.length;
        actList.appendChild(renderRow(actionCatalog, typeKey, currentBase('action', idx), params, null, 'action'));
    }

    // Populate existing
    existingConditions.forEach(c => addCondition(c.type, c.params || {}, c.operator));
    existingActions.forEach(a => addAction(a.type, a.params || {}));

    document.getElementById('add-condition').addEventListener('click', () => addCondition(conditionCatalog[0].key, null, null));
    document.getElementById('add-action').addEventListener('click', () => addAction(actionCatalog[0].key, null));
})();
</script>
@endsection
