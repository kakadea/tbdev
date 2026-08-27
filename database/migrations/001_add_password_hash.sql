-- Additive authentication migration. Keep legacy passhash during the rollout.
-- Apply once against the staging database before enabling the new login path.
ALTER TABLE users
    ADD COLUMN password_hash VARCHAR(255) NULL AFTER passhash,
    ADD COLUMN recovery_expires INT(11) NOT NULL DEFAULT 0 AFTER editsecret;
