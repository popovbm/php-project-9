CREATE TABLE IF NOT EXISTS urls (
    id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    name varchar(255),
    created_at timestamp
);

-- CREATE TABLE IF NOT EXISTS checks (
--     id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
--     name varchar(255),
--     created_at timestamp
-- );
