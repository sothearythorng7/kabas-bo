@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Values of variations")</h1>

    <a href="{{ route('variation-values.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> @t("Add value")
    </a>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th class="text-center">ID</th>
                <th>@t("warehouse_invoices.type")</th>
                <th>@t("variation.value")</th>
            </tr>
        </thead>
        <tbody>
            @foreach($values as $value)
            <tr>
                <td style="width: 1%; white-space: nowrap;">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('variation-values.edit', $value) }}">
                                    <i class="bi bi-pencil-fill"></i> @t("btn.edit")
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('variation-values.destroy', $value) }}" method="POST" onsubmit="return confirm('Confirmer la suppression ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="dropdown-item text-danger" type="submit">
                                        <i class="bi bi-trash-fill"></i> @t("btn.delete")
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
                <td class="text-center">{{ $value->id }}</td>
                <td>{{ $value->type->name ?? '-' }}</td>
                <td>{{ $value->value }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $values->links() }}
</div>
@endsection
