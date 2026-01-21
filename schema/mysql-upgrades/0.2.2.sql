ALTER TABLE testsuite ADD INDEX idx_testsuite_project_id (project_id);
ALTER TABLE testrun ADD INDEX idx_testrun_project_id (project_id);
ALTER TABLE testrun ADD INDEX idx_testrun_testsuite_id (testsuite_id);

INSERT INTO selenium_schema (version, timestamp, success)
VALUES ('0.2.2', UNIX_TIMESTAMP() * 1000, 'y');
