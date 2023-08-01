@props(['route'])

<div class="dropdown">
    <button class="btn btn-secondary text-white dropdown-toggle" type="button" id="dropdownMenuButton" data-coreui-toggle="dropdown" aria-expanded="false">
        {{ ucfirst(request()->query('status')) ?: 'Filter' }}
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{{ route($route) }}">
                All
            </a>
        </li>
        @foreach (config('definitions.statuses') as $name => $value)
            <li>
                <a class="dropdown-item" href="{{ route($route, ['status' => $value]) }}">
                    {{ $name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>