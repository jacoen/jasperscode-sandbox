<x-mail::message>
# {{ __('emails.common.greeting', ['user' => $user]) }},  

{{ __('emails.project-alert.notification') }}

{{ __('emails.project-alert.due_date', ['title' => $project->title, 'due_date' => $project->due_date->format('d-m-Y')]) }}

{{ __('emails.common.closing') }}  

{{ __('emails.common.regards') }}  
{{ config('app.name') }}
</x-mail::message>
