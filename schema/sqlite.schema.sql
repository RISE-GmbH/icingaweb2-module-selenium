CREATE TABLE project
(
    id      INTEGER PRIMARY KEY,
    name    TEXT UNIQUE,
    enabled TEXT,
    mtime   REAL,
    ctime   REAL
);

CREATE TABLE testsuite
(
    id      INTEGER PRIMARY KEY,
    name    TEXT UNIQUE,
    data    TEXT,
    enabled TEXT,
    sleep   REAL,
    ctime   REAL,
    mtime   REAL,
    project_id   INTEGER,
    generic TEXT,
    reference_object   TEXT
);

CREATE TABLE testrun
(
    id      INTEGER PRIMARY KEY,
    name    TEXT,
    status  TEXT,
    result  TEXT,
    run_ref  TEXT,
    ctime   REAL,
    mtime   REAL,
    project_id   INTEGER,
    testsuite_id   INTEGER
);
