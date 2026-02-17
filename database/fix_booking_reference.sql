-- Fix: Allow multiple booking rows (one per seat) to share the same booking_reference.
-- Run this if you get "Duplicate entry" when booking more than one seat.

-- Drop the UNIQUE constraint on booking_reference (keeps the column, allows duplicates for multi-seat bookings)
ALTER TABLE bookings DROP INDEX booking_reference;

-- Optional: add a non-unique index for faster lookups
-- CREATE INDEX idx_booking_ref ON bookings(booking_reference);
