@props(['record'])

<tr>
    <x-table-link route="projects.show" :param="$record" :content="$record->title" :limit="35"/>
    <td>{{ $record->manager->name ?? 'Not assigned' }}</td>
    <td>{{ $record->status }}</td>
    <td>{{ $record->due_date->format('d M Y') }}</td>
    <td>{{ lastUpdated($record->updated_at) }}</td>
    @if (auth()->user()->can('update project') || auth()->user()->can('delete project'))
        <td class="d-flex align-items-center">
            @can('update project')
                <a class="btn btn-sm btn-info fw-semibold text-white" href="{{ route('projects.edit', $record) }}">
                    Edit
                </a>
            @endcan
        </td>
    @endif
</tr>