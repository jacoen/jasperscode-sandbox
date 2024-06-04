<x-mail::message>
# {{ __('emails.common.greeting', ['user' => $user]) }},

{{ __('emails.expiration-report.notification') }}

{{ __('emails.expiration-report.total_expired', ['count' => $count]) }}<br>
{{ __('emails.expiration-report.check_projects') }}

<x-mail::button :url="route('projects.expired', ['yearWeek' => $yearWeek])">
{{ __('emails.expiration-report.button_text') }}
</x-mail::button>

{{ __('emails.common.closing') }}  

{{ __('emails.common.regards') }}  
{{ config('app.name') }}
</x-mail::message>
