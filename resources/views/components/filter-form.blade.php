@props(['route', 'placeholder', 'param' => null])

<form class="row justify-content-end gx-2 me-2" method="GET" action="{{ route($route, $param) }}">
    <div class="col-lg-8">
        <label for="search" class="visually-hidden">Search</label>
        <input id="search" name="search" type="text" class="form-control" value="{{ request('search') ?? '' }}" placeholder="{{ $placeholder }}">
    </div>

    <div class="col-auto">
        <select class="form-select" id="status" name="status">
            <option value="">Filter</option>
            @foreach(config('definitions.statuses') as $name => $value)
                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : ''}}>
                    {{ $name}}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-auto">
        <button type="submit" class="btn btn-info text-white fw-semibold">Search</button>
        <a href="{{ route($route, $param) }}" class="btn btn-outline-info fw-semibold ms-2 @if(!request('search') && !request('status')) disabled @endif">
            Clear
        </a>
    </div>
</form>