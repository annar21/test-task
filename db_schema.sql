-- Categories table
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Products table
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price NUMERIC(10,2) NOT NULL,
    category_id INTEGER NOT NULL REFERENCES categories(id)
);

-- Orders table
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id),
    order_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Stats table (free structure, for optimization or custom stats)
CREATE TABLE stats (
    id SERIAL PRIMARY KEY,
    key VARCHAR(255) NOT NULL,
    value TEXT
); 