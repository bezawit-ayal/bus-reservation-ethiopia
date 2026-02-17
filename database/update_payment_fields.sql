-- Add payment method and transaction ID columns to bookings table
ALTER TABLE bookings 
ADD COLUMN payment_method VARCHAR(50) NULL AFTER payment_status,
ADD COLUMN transaction_id VARCHAR(100) NULL AFTER payment_method;
