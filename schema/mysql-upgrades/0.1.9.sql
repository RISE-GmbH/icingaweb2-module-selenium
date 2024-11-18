CREATE TABLE selenium_schema (
    id        int unsigned NOT NULL AUTO_INCREMENT,
    version   varchar(64) NOT NULL,
    timestamp bigint unsigned NOT NULL,
    success   enum('n', 'y') DEFAULT NULL,
    reason    text DEFAULT NULL,

    PRIMARY KEY (id),
    CONSTRAINT idx_enrollment_schema_version UNIQUE (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

INSERT INTO selenium_schema (version, timestamp, success)
VALUES ('0.1.9', UNIX_TIMESTAMP() * 1000, 'y');

ALTER TABLE testsuite ADD COLUMN implicit_wait DECIMAL(5,2) unsigned DEFAULT 0;
