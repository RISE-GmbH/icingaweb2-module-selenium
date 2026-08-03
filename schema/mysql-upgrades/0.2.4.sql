ALTER TABLE testrun
    ADD COLUMN check_source TEXT DEFAULT NULL
    AFTER run_ref;

INSERT INTO selenium_schema (version, timestamp, success)
VALUES ('0.2.4', UNIX_TIMESTAMP() * 1000, 'y');
