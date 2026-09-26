-- Sirf naye changes. Jo database pehle se chal raha hai uspar chalao.
-- phpMyAdmin -> rushless -> SQL tab -> paste -> Go

USE rushless;

-- Phase 1 se pehle ka change (agar pehle nahi chalaya to yeh bhi chal jayega)
ALTER TABLE items ADD COLUMN is_available TINYINT(1) NOT NULL DEFAULT 1;

-- Phase 2: walk-in orders
ALTER TABLE orders ADD COLUMN source VARCHAR(10) NOT NULL DEFAULT 'online' AFTER user_id;
ALTER TABLE orders MODIFY user_id INT NULL;

-- Phase 2: rating (order collect hone ke baad student star deta hai)
CREATE TABLE ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  outlet_id INT NOT NULL,
  user_id INT NOT NULL,
  stars TINYINT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (outlet_id) REFERENCES outlets(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Phase 2: equipment-based prediction
CREATE TABLE equipment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  outlet_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  FOREIGN KEY (outlet_id) REFERENCES outlets(id)
);

ALTER TABLE items ADD COLUMN equipment_id INT NULL,
                  ADD FOREIGN KEY (equipment_id) REFERENCES equipment(id);

ALTER TABLE order_items ADD COLUMN prep_min DECIMAL(4,1) NOT NULL DEFAULT 0,
                        ADD COLUMN equipment_id INT NULL;

ALTER TABLE orders ADD COLUMN equipment_min DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER order_min;

-- Item-wise rating (search results me har item ki apni rating)
CREATE TABLE item_ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  outlet_id INT NOT NULL,
  item_name VARCHAR(80) NOT NULL,
  stars TINYINT NOT NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (outlet_id) REFERENCES outlets(id)
);
