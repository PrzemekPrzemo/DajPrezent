<x-mail::message>
# {{ __('messages.emails.reservation_verify_h1') }}

{{ __('messages.emails.reservation_verify_greeting', ['name' => $reservation->guest_name ? ', '.$reservation->guest_name : '']) }}

{!! __('messages.emails.reservation_verify_lead', ['minutes' => $ttlMinutes]) !!}

<x-mail::button :url="$verifyUrl">
{{ __('messages.emails.reservation_verify_cta') }}
</x-mail::button>

{!! __('messages.emails.reservation_verify_not_you') !!}

{!! __('messages.emails.reservation_verify_cancel', ['url' => $cancelUrl]) !!}

{{ __('messages.emails.reservation_verify_signoff') }}<br>
{{ __('messages.emails.signature') }}
</x-mail::message>
