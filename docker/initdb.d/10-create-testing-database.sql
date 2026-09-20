-- The test suite runs against PostgreSQL, never SQLite (08-environment.md), and against a
-- database of its own, so that running `pest` does not drop the development data sitting
-- next to it. phpunit.xml points DB_DATABASE here.
--
-- This runs only when the data directory is empty. An existing pgdata volume never re-runs
-- it, which is why the database also has to be created by hand the one time the volume
-- already exists (08-environment.md records the command).
--
-- The owner must match POSTGRES_USER in compose.yaml; a .sql file cannot read it.
CREATE DATABASE taskpost_testing OWNER taskpost;
