ALTER TABLE testsuite
    ADD COLUMN override_vars_file TEXT DEFAULT NULL
    AFTER reference_object;

INSERT INTO selenium_schema (version, timestamp, success)
VALUES ('0.2.3', UNIX_TIMESTAMP() * 1000, 'y');
