CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    title VARCHAR(100),
    category_id INTEGER,
    category_title VARCHAR(100),
    description TEXT,
    price NUMERIC(10, 2),
    stock_quantity INTEGER,
    origin VARCHAR(100),
    roast_level VARCHAR(50),
    flavor_notes TEXT[],
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(100),
    phone_number VARCHAR(32) UNIQUE,
    address TEXT
);

CREATE TABLE coupons (
    id SERIAL PRIMARY KEY,
    coupon_code VARCHAR(32) UNIQUE,
    discount_amount NUMERIC(10, 2),
    expiration_date DATE,
    usage_count INTEGER DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    customer_id INTEGER REFERENCES customers(id),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    coupon_code VARCHAR(32) REFERENCES coupons(coupon_code),
    product_count INTEGER,
    total_price NUMERIC(10, 2),
    discounted_percent NUMERIC(10, 2),
    discounted_price NUMERIC(10, 2),
    cargo_price NUMERIC(10, 2),
    final_price NUMERIC(10, 2),
    shipping_address TEXT,
    payment_status VARCHAR(50),
    shipping_status VARCHAR(50),
    note TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES orders(id),
    product_id INTEGER REFERENCES products(id),
    quantity INTEGER,
    price NUMERIC(10, 2)
);