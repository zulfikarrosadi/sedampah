<?php

return [
  // Maximum active plot bookings allowed per check-in date (0 = unlimited / bounded only by available plots)
  'max_daily_reservations' => (int) env('CAMPING_MAX_DAILY_RESERVATIONS', 20),

  // Minutes a pending Midtrans reservation holds a plot & equipment stock before expiring
  'pending_expiry_minutes' => (int) env('CAMPING_PENDING_EXPIRY_MINUTES', 60),
];
