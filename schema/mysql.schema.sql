CREATE TABLE project (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    name  TEXT NOT NULL,
    enabled        enum ('y', 'n')          DEFAULT 'n' NOT NULL,
    mtime bigint unsigned DEFAULT NULL,
    ctime bigint unsigned DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE testsuite (
    id int(10) unsigned NOT NULL AUTO_INCREMENT,
    name  TEXT NOT NULL,
    data  LONGTEXT NOT NULL,
    enabled        enum ('y', 'n')          DEFAULT 'n' NOT NULL,
    sleep DECIMAL(5,2) unsigned DEFAULT NULL,
    implicit_wait DECIMAL(5,2) unsigned DEFAULT 0,
    ctime bigint unsigned DEFAULT NULL,
    mtime bigint unsigned DEFAULT NULL,
    project_id int(10) unsigned NOT NULL,
    generic        enum ('y', 'n')   DEFAULT 'n' NOT NULL,
    reference_object  TEXT DEFAULT NULL,
    proxy text DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE testrun (
   id int(10) unsigned NOT NULL AUTO_INCREMENT,
   name  TEXT NOT NULL,
   status  TEXT NOT NULL,
   result  LONGTEXT NOT NULL,
   run_ref  TEXT DEFAULT NULL,
   ctime bigint unsigned DEFAULT NULL,
   mtime bigint unsigned DEFAULT NULL,
   project_id int(10) unsigned NOT NULL,
   testsuite_id int(10) unsigned NOT NULL,
   PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE selenium_schema (
    id        int unsigned NOT NULL AUTO_INCREMENT,
    version   varchar(64) NOT NULL,
    timestamp bigint unsigned NOT NULL,
    success   enum('n', 'y') DEFAULT NULL,
    reason    text DEFAULT NULL,

    PRIMARY KEY (id),
    CONSTRAINT idx_enrollment_schema_version UNIQUE (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

CREATE TABLE activity (
      id int(10) unsigned NOT NULL AUTO_INCREMENT,
      user  TEXT NOT NULL,
      model  TEXT NOT NULL,
      model_id  int(10) NOT NULL,
      action  TEXT NOT NULL,
      old  LONGTEXT NOT NULL,
      new  LONGTEXT NOT NULL,
      ctime bigint unsigned DEFAULT NULL,
      mtime bigint unsigned DEFAULT NULL,
      PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO selenium_schema (version, timestamp, success)
VALUES ('0.2.1', UNIX_TIMESTAMP() * 1000, 'y');