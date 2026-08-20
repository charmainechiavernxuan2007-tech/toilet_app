-- Run once against the existing production database when users.username is missing.
-- Existing accounts receive a temporary unique username based on their ID.
-- Admins can change these values afterward from Admin -> Users.

ALTER TABLE users
  ADD COLUMN username VARCHAR(50) NULL AFTER id;

UPDATE users
SET username = CONCAT('user_', id)
WHERE username IS NULL OR username = '';

ALTER TABLE users
  MODIFY username VARCHAR(50) NOT NULL,
  ADD UNIQUE KEY uq_users_username (username);