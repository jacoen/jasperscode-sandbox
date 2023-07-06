@props(['route', 'param', 'content', 'limit'])

<td>
    <a href="{{ route($route, $param) }}" class="text-decoration-none text-reset fw-semibold">
        <span title="{{ $content }}" @class(['fw-bold' => $param->is_pinned && auth()->user()->hasRole('Admin|Super Admin')])>
            {{ Str::limit($content, $limit) }}
        </span>
    </a>
</td>