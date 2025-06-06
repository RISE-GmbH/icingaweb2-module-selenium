ALTER TABLE testsuite ADD COLUMN proxy text DEFAULT NULL;

INSERT INTO selenium_schema (version, timestamp, success)
VALUES ('0.2.0', UNIX_TIMESTAMP() * 1000, 'y');
