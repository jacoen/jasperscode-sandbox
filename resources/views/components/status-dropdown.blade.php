@props(['route', 'param' => null, 'text' => null])

<div class="dropdown">
    <button class="btn btn-secondary text-white dropdown-toggle" type="button" id="dropdownMenuButton" data-coreui-toggle="dropdown" aria-expanded="false">
        {{ ucfirst(request()->query('status')) ?: 'Filter' }}
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="{{ route($route, $param) }}">
                All
            </a>
        </li>
        @foreach (config('definitions.statuses') as $name => $value)
            <li>
                <a class="dropdown-item" href="{{ route($route, [$text => $param, 'status' => $value]) }}">
                    {{ $name }}
                </a>
            </li>
        @endforeach
    </ul>
</div>