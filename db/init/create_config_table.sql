-- Create config table for storing application configuration
-- This script is executed automatically when the database container starts

CREATE TABLE IF NOT EXISTS config (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    value TEXT,
    core INTEGER NOT NULL DEFAULT 0,
    timecreated TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    timemodified TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create an index on the name field for faster lookups
CREATE INDEX IF NOT EXISTS idx_config_name ON config(name);

-- Create an index on the core field for filtering core vs non-core configs
CREATE INDEX IF NOT EXISTS idx_config_core ON config(core);

-- Create a function to automatically update timemodified when a row is updated
CREATE OR REPLACE FUNCTION update_timemodified_column()
RETURNS TRIGGER AS $
BEGIN
    NEW.timemodified = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$ language 'plpgsql';

-- Create a trigger to automatically update timemodified on UPDATE
CREATE TRIGGER update_config_timemodified 
    BEFORE UPDATE ON config 
    FOR EACH ROW 
    EXECUTE FUNCTION update_timemodified_column();

-- Insert some example configuration values
INSERT INTO config (name, value, core) VALUES 
    ('app_name', 'React Docker App', 1),
    ('app_version', '1.0.0', 1),
    ('maintenance_mode', 'false', 1),
    ('max_users', '1000', 0),
    ('theme_color', 'blue', 0),
    ('debug_mode', 'false', 1)
ON CONFLICT (name) DO NOTHING;

-- Add comments to the table and columns
COMMENT ON TABLE config IS 'Application configuration key-value store';
COMMENT ON COLUMN config.id IS 'Primary key auto-increment identifier';
COMMENT ON COLUMN config.name IS 'Configuration key name (unique)';
COMMENT ON COLUMN config.value IS 'Configuration value (stored as text)';
COMMENT ON COLUMN config.core IS 'Core configuration flag (1=core/system, 0=user/optional)';
COMMENT ON COLUMN config.timecreated IS 'Timestamp when the config entry was created';
COMMENT ON COLUMN config.timemodified IS 'Timestamp when the config entry was last modified';