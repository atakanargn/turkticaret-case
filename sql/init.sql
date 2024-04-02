CREATE TABLE product (
    product_id SERIAL PRIMARY KEY,
    title VARCHAR(100),
    category_id INTEGER,
    category_title VARCHAR(100),
    description TEXT,
    price NUMERIC(10, 2),
    stock_quantity INTEGER,
    origin VARCHAR(100),
    roast_level VARCHAR(50),
    flavor_notes TEXT[]
);