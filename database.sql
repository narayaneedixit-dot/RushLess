-- ============================================
-- RushLess - poora database
-- phpMyAdmin -> Import -> yeh file -> Go
-- ============================================

DROP DATABASE IF EXISTS rushless;
CREATE DATABASE rushless;
USE rushless;

-- ---------- 1. Users: college, outlet (admin) aur student ----------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role VARCHAR(10) NOT NULL,             -- college / admin / student
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,        -- hash, plain text nahi
  name VARCHAR(60),                      -- student ka naam
  roll_no VARCHAR(20),
  branch VARCHAR(30),
  semester VARCHAR(10),
  created_at DATETIME NOT NULL
);

-- ---------- 2. Outlets: sirf college banata hai ----------
CREATE TABLE outlets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,                  -- is outlet ka login account
  category VARCHAR(20) NOT NULL,         -- canteen / stationary
  name VARCHAR(80) NOT NULL,
  photo VARCHAR(255),
  is_open TINYINT(1) NOT NULL DEFAULT 0, -- outlet abhi khula hai ya band
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ---------- 3. Equipment (kaunsi machine/station outlet ke paas hai) ----------
-- Ek outlet ke paas 2 Maggi stove ho sakte hain aur 1 dosa tawa.
-- quantity = kitne units hain, yaani ek saath kitne kaam chal sakte hain.
CREATE TABLE equipment (
  id INT AUTO_INCREMENT PRIMARY KEY,
  outlet_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  FOREIGN KEY (outlet_id) REFERENCES outlets(id)
);

-- ---------- 4. Menu ----------
CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  outlet_id INT NOT NULL,
  name VARCHAR(80) NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  prep_min DECIMAL(4,1) NOT NULL DEFAULT 2,   -- ek unit banane ka time
  is_available TINYINT(1) NOT NULL DEFAULT 1, -- 0 = out of stock
  equipment_id INT NULL,                      -- yeh item kis station par banta hai (NULL = koi machine nahi chahiye)
  FOREIGN KEY (outlet_id) REFERENCES outlets(id),
  FOREIGN KEY (equipment_id) REFERENCES equipment(id)
);

-- ---------- 5. Orders ----------
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  outlet_id INT NOT NULL,
  user_id INT NULL,                               -- walk-in order mein koi student nahi hota
  source VARCHAR(10) NOT NULL DEFAULT 'online',   -- online / walkin
  token VARCHAR(12) NOT NULL DEFAULT '',
  status VARCHAR(15) NOT NULL DEFAULT 'pending',  -- pending / preparing / ready / collected
  amount DECIMAL(8,2) NOT NULL,
  -- prediction ka hisaab, taaki predicted vs actual compare ho sake
  queue_count INT NOT NULL,
  avg_service_min DECIMAL(6,2) NOT NULL,
  queue_min DECIMAL(6,2) NOT NULL,
  slot_min DECIMAL(6,2) NOT NULL DEFAULT 0,
  order_min DECIMAL(6,2) NOT NULL,
  equipment_min DECIMAL(6,2) NOT NULL DEFAULT 0,  -- station ka backlog kitna tha
  predicted_min DECIMAL(6,2) NOT NULL,
  is_provisional TINYINT(1) NOT NULL,
  created_at DATETIME NOT NULL,
  started_at DATETIME NULL,
  ready_at DATETIME NULL,
  collected_at DATETIME NULL,
  FOREIGN KEY (outlet_id) REFERENCES outlets(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ---------- 6. Order ke items ----------
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  item_name VARCHAR(80) NOT NULL,
  qty INT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  -- yeh do sirf prediction ke liye hain: queue mein kaun sa station kitna busy hai
  prep_min DECIMAL(4,1) NOT NULL DEFAULT 0,
  equipment_id INT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- ---------- 6. Asli service time (prediction isi se seekhta hai) ----------
CREATE TABLE service_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NULL,
  outlet_id INT NOT NULL,
  service_sec INT NOT NULL,              -- Start se Ready tak ka asli time
  logged_at DATETIME NOT NULL,
  FOREIGN KEY (outlet_id) REFERENCES outlets(id)
);

-- ---------- 8. Har ghante ka rush adjustment ----------
CREATE TABLE slot_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  hour_of_day TINYINT NOT NULL UNIQUE,   -- 0 se 23
  adjustment_min DECIMAL(5,2) NOT NULL   -- us ghante mein kitne extra minute lagte hain
);

-- Lunch aur break ke time queue lambi hoti hai, isliye extra minute
INSERT INTO slot_stats (hour_of_day, adjustment_min) VALUES
(8,1),(9,1),(10,0),(11,2),(12,4),(13,5),(14,2),(15,0),(16,1),(17,2),(18,1);

-- ---------- 9. Rating ----------
-- Order collect hone ke baad student 1 se 5 star deta hai.
-- order_id UNIQUE hai, isliye ek order par sirf ek hi rating ho sakti hai.
CREATE TABLE ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  outlet_id INT NOT NULL,
  user_id INT NOT NULL,
  stars TINYINT NOT NULL,                -- 1 se 5
  created_at DATETIME NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (outlet_id) REFERENCES outlets(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Item-wise rating: order ke har item ko wahi star milta hai jo order ko diya gaya.
-- Item ko naam se pehchante hain (order_items bhi naam se store hota hai), taaki
-- menu badalne par purani rating na toote.
CREATE TABLE item_ratings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  outlet_id INT NOT NULL,
  item_name VARCHAR(80) NOT NULL,
  stars TINYINT NOT NULL,                -- 1 se 5
  created_at DATETIME NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (outlet_id) REFERENCES outlets(id)
);

-- ---------- College ka master account ----------
-- Email: college@rushless.com   Password: college123
-- (Yeh signup se nahi banta, isliye koi bahar se college account nahi bana sakta)
INSERT INTO users (role, email, password, created_at) VALUES
('college', 'college@rushless.com', '$2y$10$Pr6ReBlMzb06psgV06H9k.d5euZBhjg94SZFFi7Q0uZl9wYwuRMPW', NOW());
