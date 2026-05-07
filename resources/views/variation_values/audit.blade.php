@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">{{ __('messages.variation_value.audit_title') }}</h1>

    <p class="text-muted small">{{ __('messages.variation_value.audit_intro') }}</p>

    <div class="row mb-3 g-2">
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body p-2 text-center">
                    <div class="small text-muted">{{ __('messages.variation_value.audit_stats_total') }}</div>
                    <div class="fs-4 fw-bold">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body p-2 text-center">
                    <div class="small text-muted">{{ __('messages.variation_value.audit_stats_issues') }}</div>
                    <div class="fs-4 fw-bold text-warning">{{ $stats['with_issues'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body p-2 text-center">
                    <div class="small text-muted">{{ __('messages.variation_value.audit_stats_orphans') }}</div>
                    <div class="fs-4 fw-bold text-danger">{{ $stats['orphans'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body p-2 text-center">
                    <div class="small text-muted">{{ __('messages.variation_value.audit_stats_decided') }}</div>
                    <div class="fs-4 fw-bold text-success">{{ $stats['decided'] }}</div>
                </div>
            </div>
        </div>
    </div>

    @php
        $allTypesJson = $allTypes->map(fn($t) => ['id' => $t->id, 'name' => $t->label ?: $t->name])->values();
    @endphp

    @if($byType->isEmpty())
        <div class="alert alert-success">{{ __('messages.variation_value.audit_no_issues') }}</div>
    @endif

    @foreach($byType as $typeId => $rows)
        @php $type = $types[$typeId] ?? null; @endphp
        <div class="card mb-4">
            <div class="card-header bg-light">
                <strong>{{ $type?->label ?: $type?->name ?: '?' }}</strong>
                <span class="badge bg-secondary ms-2">{{ count($rows) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle audit-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">{{ __('messages.variation_value.audit_col_id') }}</th>
                            <th style="min-width: 180px;">{{ __('messages.variation_value.audit_col_value') }}</th>
                            <th style="width: 80px;">{{ __('messages.variation_value.audit_col_count') }}</th>
                            <th style="min-width: 200px;">{{ __('messages.variation_value.audit_col_issues') }}</th>
                            <th style="min-width: 220px;">{{ __('messages.variation_value.audit_col_samples') }}</th>
                            <th style="min-width: 350px;">{{ __('messages.variation_value.audit_col_decision') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php
                                $v = $row['value'];
                                $decision = $v->audit_decision ?: [];
                                $action = $decision['action'] ?? '';
                            @endphp
                            <tr data-value-id="{{ $v->id }}">
                                <td class="text-muted small">{{ $v->id }}</td>
                                <td>
                                    @if($v->color_hex)
                                        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;border:1px solid #ccc;background:{{ $v->color_hex }};vertical-align:middle;margin-right:4px;"></span>
                                    @endif
                                    <strong>{{ $v->value }}</strong>
                                    @if($v->color_hex)
                                        <small class="text-muted d-block">{{ $v->color_hex }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($row['count'] > 0)
                                        <span class="badge bg-secondary">{{ $row['count'] }}</span>
                                    @else
                                        <span class="badge bg-danger">0</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($row['reasons'] as $reason)
                                        <span class="badge bg-warning text-dark me-1 mb-1">
                                            {{ __('messages.variation_value.audit_reason_' . $reason) }}
                                        </span>
                                    @endforeach
                                    @if($row['count'] === 0 && empty($row['reasons']))
                                        <span class="badge bg-danger me-1 mb-1">
                                            {{ __('messages.variation_value.audit_reason_orphan') }}
                                        </span>
                                    @endif
                                    @if($row['suggestion'])
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-lightbulb"></i>
                                            {{ __('messages.variation_value.audit_suggestion') }}:
                                            <code>{{ $row['suggestion']['action'] }}</code>
                                            @if(!empty($row['suggestion']['target']))
                                                → <code>{{ $row['suggestion']['target'] }}</code>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @foreach($row['samples'] as $sample)
                                        <a href="{{ route('products.edit', $sample['id']) }}" target="_blank" class="d-block small text-truncate" style="max-width: 220px;" title="{{ $sample['name'] }}">
                                            #{{ $sample['id'] }} {{ $sample['name'] }}
                                        </a>
                                    @endforeach
                                </td>
                                <td>
                                    <form class="audit-form" data-value-id="{{ $v->id }}">
                                        @csrf
                                        <div class="d-flex gap-1 flex-wrap mb-1">
                                            <select name="action" class="form-select form-select-sm audit-action" style="width: auto;">
                                                <option value="">{{ __('messages.variation_value.audit_action_none') }}</option>
                                                <option value="keep" {{ $action === 'keep' ? 'selected' : '' }}>{{ __('messages.variation_value.audit_action_keep') }}</option>
                                                <option value="rename" {{ $action === 'rename' ? 'selected' : '' }}>{{ __('messages.variation_value.audit_action_rename') }}</option>
                                                <option value="move" {{ $action === 'move' ? 'selected' : '' }}>{{ __('messages.variation_value.audit_action_move') }}</option>
                                                <option value="split" {{ $action === 'split' ? 'selected' : '' }}>{{ __('messages.variation_value.audit_action_split') }}</option>
                                                <option value="merge" {{ $action === 'merge' ? 'selected' : '' }}>{{ __('messages.variation_value.audit_action_merge') }}</option>
                                                <option value="delete" {{ $action === 'delete' ? 'selected' : '' }}>{{ __('messages.variation_value.audit_action_delete') }}</option>
                                            </select>

                                            <input type="text" name="new_value" class="form-control form-control-sm field-rename d-none" placeholder="{{ __('messages.variation_value.audit_new_value') }}" value="{{ $decision['new_value'] ?? '' }}" style="max-width: 160px;">

                                            <select name="target_type_id" class="form-select form-select-sm field-move d-none" style="width: auto;">
                                                <option value="">{{ __('messages.variation_value.audit_target_type') }}</option>
                                                @foreach($allTypes as $t)
                                                    <option value="{{ $t->id }}" {{ ($decision['target_type_id'] ?? null) == $t->id ? 'selected' : '' }}>{{ $t->label ?: $t->name }}</option>
                                                @endforeach
                                            </select>

                                            <input type="number" name="merge_into_id" class="form-control form-control-sm field-merge d-none" placeholder="ID" value="{{ $decision['merge_into_id'] ?? '' }}" style="max-width: 100px;">

                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bi bi-save"></i> {{ __('messages.variation_value.audit_save') }}
                                            </button>
                                            @if(!empty($decision))
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-clear" title="{{ __('messages.variation_value.audit_action_clear') }}">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <div class="field-split d-none mb-1">
                                            <div class="small text-muted mb-1">{{ __('messages.variation_value.audit_split_helper') }}</div>
                                            <div class="split-rows">
                                                @foreach(($decision['split_into'] ?? [['type_id'=>'','value'=>''],['type_id'=>'','value'=>'']]) as $i => $piece)
                                                    <div class="d-flex gap-1 mb-1 split-row">
                                                        <select name="split_into[{{ $i }}][type_id]" class="form-select form-select-sm" style="max-width: 130px;">
                                                            <option value="">—</option>
                                                            @foreach($allTypes as $t)
                                                                <option value="{{ $t->id }}" {{ ($piece['type_id'] ?? '') == $t->id ? 'selected' : '' }}>{{ $t->label ?: $t->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="text" name="split_into[{{ $i }}][value]" class="form-control form-control-sm" placeholder="value" value="{{ $piece['value'] ?? '' }}" style="max-width: 140px;">
                                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-split"><i class="bi bi-x"></i></button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-split">
                                                <i class="bi bi-plus"></i> {{ __('messages.variation_value.audit_split_add') }}
                                            </button>
                                        </div>

                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('messages.variation_value.audit_notes') }}" value="{{ $decision['notes'] ?? '' }}">

                                        <div class="audit-status small mt-1">
                                            @if($v->audit_decided_at)
                                                <span class="text-success">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    {{ __('messages.variation_value.audit_decided_at') }} {{ $v->audit_decided_at->format('Y-m-d H:i') }}
                                                </span>
                                            @endif
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

<style>
    .audit-table td { vertical-align: top; padding: 0.4rem; }
    .audit-form .audit-status .text-success,
    .audit-form .audit-status .text-danger { font-size: 0.75rem; }
</style>

<script>
(function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function toggleFields(form) {
        const action = form.querySelector('.audit-action').value;
        form.querySelector('.field-rename').classList.toggle('d-none', action !== 'rename');
        form.querySelector('.field-move').classList.toggle('d-none', action !== 'move');
        form.querySelector('.field-merge').classList.toggle('d-none', action !== 'merge');
        form.querySelector('.field-split').classList.toggle('d-none', action !== 'split');
    }

    document.querySelectorAll('.audit-form').forEach(form => {
        toggleFields(form);

        form.querySelector('.audit-action').addEventListener('change', () => toggleFields(form));

        // Add split row
        const splitContainer = form.querySelector('.split-rows');
        const addBtn = form.querySelector('.btn-add-split');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                const idx = splitContainer.querySelectorAll('.split-row').length;
                const tplRow = splitContainer.querySelector('.split-row');
                if (!tplRow) return;
                const clone = tplRow.cloneNode(true);
                clone.querySelectorAll('select, input').forEach(el => {
                    el.name = el.name.replace(/\[\d+\]/, `[${idx}]`);
                    if (el.tagName === 'INPUT') el.value = '';
                    if (el.tagName === 'SELECT') el.selectedIndex = 0;
                });
                splitContainer.appendChild(clone);
            });
        }

        splitContainer?.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-remove-split');
            if (!btn) return;
            const rows = splitContainer.querySelectorAll('.split-row');
            if (rows.length <= 1) return;
            btn.closest('.split-row').remove();
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const valueId = form.dataset.valueId;
            const action = form.querySelector('.audit-action').value;
            if (!action) return;

            // Build a clean payload so we only send fields relevant to the chosen action.
            const fd = new FormData();
            fd.append('action', action);
            const notes = form.querySelector('input[name="notes"]')?.value?.trim();
            if (notes) fd.append('notes', notes);

            if (action === 'rename') {
                fd.append('new_value', form.querySelector('input[name="new_value"]')?.value?.trim() || '');
            } else if (action === 'move') {
                fd.append('target_type_id', form.querySelector('select[name="target_type_id"]')?.value || '');
            } else if (action === 'merge') {
                fd.append('merge_into_id', form.querySelector('input[name="merge_into_id"]')?.value || '');
            } else if (action === 'split') {
                const splitRows = form.querySelectorAll('.split-rows .split-row');
                let i = 0;
                splitRows.forEach(row => {
                    const tid = row.querySelector('select')?.value;
                    const val = row.querySelector('input')?.value?.trim();
                    if (tid && val) {
                        fd.append(`split_into[${i}][type_id]`, tid);
                        fd.append(`split_into[${i}][value]`, val);
                        i++;
                    }
                });
            }

            const formData = fd;
            const status = form.querySelector('.audit-status');
            status.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split"></i> ...</span>';

            try {
                const resp = await fetch(`/variation-values/${valueId}/audit-decision`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                if (!resp.ok) {
                    const errBody = await resp.text();
                    throw new Error(errBody.substring(0, 200));
                }
                const data = await resp.json();
                if (data.cleared) {
                    status.innerHTML = '<span class="text-muted"><i class="bi bi-eraser"></i> cleared</span>';
                } else {
                    status.innerHTML = `<span class="text-success"><i class="bi bi-check-circle-fill"></i> @lang('messages.variation_value.audit_saved')</span>`;
                }
            } catch (err) {
                status.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> @lang('messages.variation_value.audit_save_error'): ${err.message || err}</span>`;
            }
        });

        const clearBtn = form.querySelector('.btn-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', async () => {
                const valueId = form.dataset.valueId;
                const fd = new FormData();
                fd.append('action', 'clear');
                const status = form.querySelector('.audit-status');
                status.innerHTML = '<span class="text-muted">...</span>';
                try {
                    const resp = await fetch(`/variation-values/${valueId}/audit-decision`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: fd,
                    });
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    form.querySelector('.audit-action').value = '';
                    toggleFields(form);
                    form.querySelector('input[name="notes"]').value = '';
                    status.innerHTML = '<span class="text-muted"><i class="bi bi-eraser"></i> cleared</span>';
                    clearBtn.remove();
                } catch (err) {
                    status.innerHTML = `<span class="text-danger">${err.message}</span>`;
                }
            });
        }
    });
})();
</script>
@endsection
