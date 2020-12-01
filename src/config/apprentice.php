<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Request Timeout
  |--------------------------------------------------------------------------
  |
  | Timeout in seconds while waiting for command response or user input.
  | If no response is received within this timeframe then
  | the process is stopped with status timeout
  |
  */

  'timeout' => 10,

  /*
  |--------------------------------------------------------------------------
  | Invitation Expiration
  |--------------------------------------------------------------------------
  |
  | How many seconds an invitation is valid for before expiring.
  | Invitations also expire after being used. The default is
  | 24 hours.
  |
  */

  'invitation_expiration' => 86400,

  /*
  |--------------------------------------------------------------------------
  | Apprentice Path Prefix
  |--------------------------------------------------------------------------
  |
  | The prefix for apprentice routes. You generally won't need
  | to change this unless you are already using '/apprentice'
  | or simply prefer to rename the path to something else.
  |
  */

  'path' => '/apprentice',
];
