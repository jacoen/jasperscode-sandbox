<x-mail::message>
# {{ __('expiration-report.greeting', ['user' => $user]) }},

{{ __('expiration-report.notification') }}
{{ __('expiration-report.total_expired', ['count' => $count]) }}<br>
{{ __('expiration-report.check_projects') }}

<x-mail::button :url="route('projects.expired', ['yearWeek' => $yearWeek])">
{{ __('expiration-report.button_text') }}
</x-mail::button>

{{ __('expiration-report.closing') }}

{{ __('expiration-report.regard') }}<br>
{{ config('app.name') }}
</x-mail::message>
