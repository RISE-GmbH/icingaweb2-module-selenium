CREATE TABLE activity (
                           id int(10) unsigned NOT NULL AUTO_INCREMENT,
                           user  TEXT NOT NULL,
                           model  TEXT NOT NULL,
                           model_id  int(10) DEFAULT NULL,
                           action  TEXT NOT NULL,
                           old  LONGTEXT NOT NULL,
                           new  LONGTEXT NOT NULL,
                           ctime bigint unsigned DEFAULT NULL,
                           mtime bigint unsigned DEFAULT NULL,
                           PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO selenium_schema (version, timestamp, success)
VALUES ('0.2.1', UNIX_TIMESTAMP() * 1000, 'y');
