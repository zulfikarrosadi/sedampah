<?php

return [
  // Maximum active reservations allowed per check-in date
  'max_daily_reservations' => (int) env('CAMPING_MAX_DAILY_RESERVATIONS', 50),

  // Minutes a pending Midtrans reservation holds plots & equipment stock before expiring
  'pending_expiry_minutes' => (int) env('CAMPING_PENDING_EXPIRY_MINUTES', 60),
];
