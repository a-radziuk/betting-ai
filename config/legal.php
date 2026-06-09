<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal Page Content Parameters
    |--------------------------------------------------------------------------
    |
    | Placeholders in legal page HTML are replaced on the public site:
    | [DATE], [WEBSITE NAME], [CONTACT EMAIL], [WEBSITE URL]
    |
    */

    'date' => env('LEGAL_DATE', ''),

    'contact_email' => env('CONTACT_EMAIL', ''),

];
