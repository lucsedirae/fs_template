-- Create user table for storing user accounts
-- This script is executed automatically when the database container starts

CREATE TABLE IF NOT EXISTS "user" (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    timecreated INTEGER NOT NULL DEFAULT EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::INTEGER,
    timemodified INTEGER NOT NULL DEFAULT EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::INTEGER
);

-- Create an index on the username field for faster lookups
CREATE INDEX IF NOT EXISTS idx_user_username ON "user"(username);

-- Create an index on timecreated for sorting by creation date
CREATE INDEX IF NOT EXISTS idx_user_timecreated ON "user"(timecreated);

-- Create a function to automatically update timemodified when a row is updated
CREATE OR REPLACE FUNCTION update_user_timemodified()
RETURNS TRIGGER AS $$
BEGIN
    NEW.timemodified = EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::INTEGER;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Create a trigger to automatically update timemodified on UPDATE
CREATE TRIGGER update_user_timemodified 
    BEFORE UPDATE ON "user"
    FOR EACH ROW 
    EXECUTE FUNCTION update_user_timemodified();

-- Insert some example user data
INSERT INTO "user" (username) VALUES 
    ('admin'),
    ('testuser'),
    ('developer'),
    ('guest')
ON CONFLICT (username) DO NOTHING;

-- Add comments to the table and columns
COMMENT ON TABLE "user" IS 'User accounts table for application authentication and management';
COMMENT ON COLUMN "user".id IS 'Primary key auto-increment identifier';
COMMENT ON COLUMN "user".username IS 'Unique username for user identification';
COMMENT ON COLUMN "user".timecreated IS 'Unix timestamp when the user account was created';
COMMENT ON COLUMN "user".timemodified IS 'Unix timestamp when the user account was last modified';