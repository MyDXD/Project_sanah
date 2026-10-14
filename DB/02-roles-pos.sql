-- Additive, repeatable migration. Historical costs remain unknown, never inferred.
ALTER TABLE userdata MODIFY role ENUM('user','employee','admin') NOT NULL DEFAULT 'user';
ALTER TABLE userdata ADD COLUMN IF NOT EXISTS is_active TINYINT NOT NULL DEFAULT 1;
ALTER TABLE userdata ADD UNIQUE INDEX IF NOT EXISTS userdata_email_unique (email);
ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(10,2) NULL;
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active TINYINT NOT NULL DEFAULT 1;
ALTER TABLE products ADD COLUMN IF NOT EXISTS low_stock_threshold INT NOT NULL DEFAULT 5;
ALTER TABLE products MODIFY product_detail TEXT NOT NULL;
ALTER TABLE orders MODIFY transport_id VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) NOT NULL DEFAULT 'unverified';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS stock_restored TINYINT NOT NULL DEFAULT 0;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS unit_cost DECIMAL(10,2) NULL;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS product_name VARCHAR(255) NOT NULL DEFAULT '';
UPDATE order_items i JOIN products p ON p.product_id=i.product_id SET i.product_name=p.product_name WHERE i.product_name='';
UPDATE orders SET order_status='Order Placed' WHERE order_status IN ('Pending','Payment Received');
UPDATE orders SET payment_status='paid' WHERE order_status IN ('Completed','Shipped') AND payment_status='unverified';
-- Legacy cancellations have uncertain stock accounting. Do not restore them again.
UPDATE orders SET stock_restored=1 WHERE order_status='Cancelled';
CREATE TABLE IF NOT EXISTS stock_movements (
 id INT PRIMARY KEY AUTO_INCREMENT, product_id INT NOT NULL, actor_id INT NULL,
 delta INT NOT NULL, balance INT NOT NULL, reason VARCHAR(255) NOT NULL,
 reference_code VARCHAR(100) NOT NULL DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(product_id), INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO stock_movements (product_id,delta,balance,reason)
 SELECT p.product_id,p.stock,p.stock,'ยอดยกมาตอนเริ่มระบบ'
 FROM products p WHERE NOT EXISTS (SELECT 1 FROM stock_movements s WHERE s.product_id=p.product_id);
CREATE TABLE IF NOT EXISTS pos_sales (
 id INT PRIMARY KEY AUTO_INCREMENT, sale_code VARCHAR(60) NOT NULL UNIQUE,
 actor_id INT NOT NULL, total DECIMAL(10,2) NOT NULL DEFAULT 0,
 payment_method VARCHAR(30) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'Completed',
 note VARCHAR(255) NOT NULL DEFAULT '', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS pos_items (
 id INT PRIMARY KEY AUTO_INCREMENT, sale_id INT NOT NULL, product_id INT NOT NULL,
 product_name VARCHAR(255) NOT NULL, quantity INT NOT NULL, price DECIMAL(10,2) NOT NULL,
 unit_cost DECIMAL(10,2) NULL, FOREIGN KEY(sale_id) REFERENCES pos_sales(id), INDEX(product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS reports (
 report_id INT PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL,
 issue_title VARCHAR(255) NOT NULL, issue_description TEXT NOT NULL,
 issue_attachment VARCHAR(255) NOT NULL DEFAULT '', admin_response TEXT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'new', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 replied_by INT NULL, replied_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS admin_response TEXT NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS status VARCHAR(30) NOT NULL DEFAULT 'new';
ALTER TABLE reports ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS replied_by INT NULL;
ALTER TABLE reports ADD COLUMN IF NOT EXISTS replied_at DATETIME NULL;
CREATE TABLE IF NOT EXISTS shop_settings (
 setting_key VARCHAR(60) PRIMARY KEY, setting_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO shop_settings VALUES ('bank_details',''),('payment_qr','');
CREATE TABLE IF NOT EXISTS audit_log (
 id INT PRIMARY KEY AUTO_INCREMENT, actor_id INT NOT NULL, action VARCHAR(100) NOT NULL,
 details TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
