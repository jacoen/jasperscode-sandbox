@extends('layouts.app')

@section('content')
    <div class="card mb-4">
        <div class="card-header">
            <h3>Activities</h3>
        </div>

        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Event</th>
                        <th>Subject type</th>
                        <th>Subject id</th>
                        <th>Causer</th>
                        <th>Old data</th>
                        <th>New data</th>
                        <th>Date time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr>
                            <td>{{ $activity->id }}</td>
                            <td>{{ $activity->event }}</td>
                            <td>{{ $activity->subject_type }}</td>
                            <td>{{ $activity->subject_id }}</td>
                            <td>{{ $activity->causer->name ?? 'N/A' }}</td>
                            <td>
                                @if (array_key_exists('old', $activity->properties->toArray()))
                                    @foreach ($activity->changes['old'] as $key => $value )
                                        <span class="fw-bold">{{ $key }}</span> {{ $value }} <br />
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @if (array_key_exists('attributes', $activity->properties->toArray()))
                                    @foreach($activity->changes['attributes'] as $key => $value)
                                        <strong>{{ $key }}:</strong> {{ $value }}<br>
                                    @endforeach
                                @endif
                            </td>
                            <td scope="col">{{ $activity->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <x-pagination :records="$activities" />
        </div>
    </div>
@endsection