-- Additive authentication migration. Keep legacy passhash during the rollout.
-- Apply once against the staging database before enabling the new login path.
ALTER TABLE users
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER passhash;
