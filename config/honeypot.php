<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Honeypot enabled
  |--------------------------------------------------------------------------
  |
  | When false, bot checks are skipped (useful in tests).
  |
  */

  'enabled' => filter_var(env('HONEYPOT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

  /*
  |--------------------------------------------------------------------------
  | Honeypot field name
  |--------------------------------------------------------------------------
  |
  | Hidden text field bots tend to fill. Must remain empty for real users.
  |
  */

  'field_name' => env('HONEYPOT_FIELD_NAME', 'company_website'),

  /*
  |--------------------------------------------------------------------------
  | Timestamp field name
  |--------------------------------------------------------------------------
  |
  | Encrypted timestamp set when the form is rendered. Submissions faster than
  | minimum_submission_seconds are treated as bots.
  |
  */

  'timestamp_field' => env('HONEYPOT_TIMESTAMP_FIELD', 'form_started_at'),

  'minimum_submission_seconds' => (int) env('HONEYPOT_MIN_SECONDS', 2),

  'maximum_submission_seconds' => (int) env('HONEYPOT_MAX_SECONDS', 3600),

];
