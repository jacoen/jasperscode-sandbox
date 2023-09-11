<ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
    <li class="nav-item">
        <a class="nav-link" href="{{ route('home') }}">
            <svg class="nav-icon">
            </svg>
            {{ __('Dashboard') }}
        </a>
    </li>

    @can('read user')      
        <li class="nav-item">
            <a class="nav-link" href="{{ route('users.index') }}">
                <svg class="nav-icon">
                </svg>
                {{ __('Users') }}
            </a>
        </li>
    @endcan


    @can('read project')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('projects.index') }}">
                <svg class="nav-icon">
                </svg>
                {{ __('Projects') }}
            </a>
        </li>
    @endcan

    @can('restore project')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('projects.trashed') }}">
                <svg class="nav-icon">
                </svg>
                {{ __('trashed projects') }}
            </a>
        </li>
    @endcan

    @can('read task')
        @hasanyrole('Admin|Super Admin')
            <li class="nav-group" aria-expanded="false">
                <a class="nav-link nav-group-toggle" href="#">
                    <svg class="nav-icon"></svg>
                    Tasks
                </a>
                <ul class="nav-group-items">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('tasks.index') }}">
                            All tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('tasks.user')}}">
                            My tasks
                        </a>
                    </li>
                </ul>
        @else
            <li class="nav-item">
                <a class="nav-link" href="{{ route('tasks.user') }}">
                    <svg class="nav-icon">
                    </svg>
                    {{ __('Tasks') }}
                </a>
            </li>
        @endhasanyrole
    @endcan

    @can('restore task')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('tasks.trashed') }}">
                <svg class="nav-icon">
                </svg>
                {{ __('Trashed tasks') }}
            </a>
        </li>
    @endcan

    {{-- <li class="nav-group" aria-expanded="false">
        <a class="nav-link nav-group-toggle" href="#">
            <svg class="nav-icon">
            </svg>
            Two-level menu
        </a>
        <ul class="nav-group-items" style="height: 0px;">
            <li class="nav-item">
                <a class="nav-link" href="#" target="_top">
                    <svg class="nav-icon">
                    </svg>
                    Child menu
                </a>
            </li>
        </ul>
    </li> --}}
</ul>