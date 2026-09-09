<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Phone OTP throttling
    |--------------------------------------------------------------------------
    |
    | These knobs decide how many verification SMS we are willing to push to
    | Twilio. They exist to keep a burst of concurrent registrations — or a
    | bot farm — from ever hammering the Twilio Verify API, which reacts by
    | rate-limiting / locking the number and leaving real users stuck.
    |
    | All values are env-tunable so production can be retuned without a deploy.
    |
    */

    // Minimum seconds between two OTP sends to the SAME phone number.
    // A retry inside this window is answered locally and never reaches Twilio.
    'cooldown_seconds' => (int) env('OTP_COOLDOWN_SECONDS', 90),

    // Hard ceiling of OTP sends to a single phone number per rolling 24h.
    'max_per_phone_per_day' => (int) env('OTP_MAX_PER_PHONE_PER_DAY', 5),

    // Hard ceiling of OTP sends from a single client IP per rolling 24h.
    'max_per_ip_per_day' => (int) env('OTP_MAX_PER_IP_PER_DAY', 15),

    // Max failed verify (code check) attempts per phone before we make them
    // request a fresh code. Keeps us under Twilio's own check-attempt lock.
    'max_check_attempts' => (int) env('OTP_MAX_CHECK_ATTEMPTS', 6),
    'check_attempt_window_seconds' => (int) env('OTP_CHECK_ATTEMPT_WINDOW_SECONDS', 600),

    /*
    | Global drip to Twilio. The queued SendPhoneOtpJob is rate-limited to this
    | many sends per minute across the WHOLE app. Anything over the limit waits
    | in the queue instead of being fired at Twilio. 60/min ≈ one per second.
    */
    'twilio_sends_per_minute' => (int) env('OTP_TWILIO_SENDS_PER_MINUTE', 60),

    // Queue connection + name the OTP job is dispatched onto.
    'queue_connection' => env('OTP_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
    'queue' => env('OTP_QUEUE', 'default'),

];
