@can('admin')
    @php $q = request()->except('page'); @endphp
    <a href="{{ route('attendances.export', ['format' => 'xlsx'] + $q) }}" class="btn btn-secondary"><x-icon name="download" class="w-4 h-4 text-ok"/> Excel</a>
    <a href="{{ route('attendances.export', ['format' => 'csv'] + $q) }}" class="btn btn-secondary"><x-icon name="download" class="w-4 h-4 text-sky-700"/> CSV</a>
    <a href="{{ route('attendances.export', ['format' => 'pdf'] + $q) }}" class="btn btn-primary"><x-icon name="doc" class="w-4 h-4"/> PDF</a>
@endcan
