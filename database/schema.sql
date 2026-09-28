CREATE DATABASE IF NOT EXISTS bookbang_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE bookbang_db;

CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL DEFAULT '',
  role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
  phone VARCHAR(50) NULL,
  google_id VARCHAR(255) NULL,
  facebook_id VARCHAR(255) NULL,
  avatar VARCHAR(500) NULL,
  profile_picture VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_google_id (google_id),
  UNIQUE KEY uq_users_facebook_id (facebook_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS books (
  book_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  genre VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(500) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  discount DECIMAL(5,2) NOT NULL DEFAULT 0,
  pages INT UNSIGNED NOT NULL DEFAULT 0,
  collection VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (book_id),
  KEY idx_books_genre (genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carts (
  cart_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  session_id VARCHAR(128) NULL,
  book_id INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_id),
  KEY idx_carts_user (user_id),
  KEY idx_carts_session (session_id),
  KEY idx_carts_book (book_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_carts_book FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weekly_featured (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  section VARCHAR(50) NOT NULL,
  book_ids VARCHAR(255) NOT NULL DEFAULT '',
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_weekly_featured_section_id (section, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS library (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  session_id VARCHAR(128) NULL,
  book_id INT UNSIGNED NOT NULL,
  added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_library_user_book (user_id, book_id),
  UNIQUE KEY uq_library_session_book (session_id, book_id),
  CONSTRAINT fk_library_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_library_book FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  review_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_id INT UNSIGNED NOT NULL,
  username VARCHAR(100) NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  review_text TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  KEY idx_reviews_book (book_id),
  CONSTRAINT fk_reviews_book FOREIGN KEY (book_id) REFERENCES books (book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transactions (
  transaction_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  session_id VARCHAR(128) NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NULL,
  payment_method VARCHAR(50) NOT NULL,
  payment_data JSON NULL,
  total DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (transaction_id),
  KEY idx_transactions_user_created (user_id, created_at),
  KEY idx_transactions_session (session_id),
  CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transaction_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  transaction_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  qty INT UNSIGNED NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_transaction_items_book (book_id),
  CONSTRAINT fk_transaction_items_transaction FOREIGN KEY (transaction_id) REFERENCES transactions (transaction_id) ON DELETE CASCADE,
  CONSTRAINT fk_transaction_items_book FOREIGN KEY (book_id) REFERENCES books (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO books (book_id, title, author, genre, description, image, price, discount, pages, collection) VALUES
  (1, 'The Enigma of Arrival', 'V.S. Naipaul', 'Literary Fiction', '', 'ProductPageAssets/1Classic/c1.png', 595.00, 0, 368, 'Picador Collection; Nobel Winner'),
  (2, 'Rosy Is My Relative', 'Gerald Durrell', 'Comic Novel', '', 'ProductPageAssets/1Classic/c2.jpg', 425.00, 0, 240, 'Paperback; Uproarious Comedy'),
  (3, 'The Picture of Dorian Gray', 'Oscar Wilde', 'Classic Fiction', '', 'ProductPageAssets/1Classic/c3.jpg', 499.00, 0, 304, 'Timeless Morality Tale'),
  (4, 'Pride and Prejudice', 'Jane Austen', 'Romance', '', 'ProductPageAssets/1Classic/c4.jpg', 550.00, 0, 352, 'Penguin Edition'),
  (5, 'Great Expectations', 'Charles Dickens', 'Classic Literature', '', 'ProductPageAssets/1Classic/c5.jpg', 620.00, 0, 480, 'Illustrated Edition'),
  (6, 'The Hobbit', 'J.R.R. Tolkien', 'Fantasy', '', 'ProductPageAssets/2Fantasy/f1.jpg', 699.00, 0, 320, 'Illustrated Adventure'),
  (7, 'Harry Potter and the Sorcerer''s Stone', 'J.K. Rowling', 'Fantasy', '', 'ProductPageAssets/2Fantasy/f2.jpg', 750.00, 0, 340, 'First Edition Print'),
  (8, 'The Name of the Wind', 'Patrick Rothfuss', 'Epic Fantasy', '', 'ProductPageAssets/2Fantasy/f3.jpg', 899.00, 0, 662, 'Kingkiller Chronicle Series'),
  (9, 'A Game of Thrones', 'George R.R. Martin', 'Fantasy', '', 'ProductPageAssets/2Fantasy/f4.jpg', 899.00, 0, 694, 'Song of Ice and Fire Series'),
  (10, 'The Way of Kings', 'Brandon Sanderson', 'Epic Fantasy', '', 'ProductPageAssets/2Fantasy/f5.jpg', 950.00, 0, 1007, 'Stormlight Archive Series'),
  (11, 'Dune', 'Frank Herbert', 'Science Fiction', '', 'ProductPageAssets/3ScienceFiction/sf1.jpg', 890.00, 0, 688, 'Hugo and Nebula Winner'),
  (12, 'Neuromancer', 'William Gibson', 'Cyberpunk', '', 'ProductPageAssets/3ScienceFiction/sf2.jpg', 750.00, 0, 271, 'Classic Cyberpunk Novel'),
  (13, 'Ender''s Game', 'Orson Scott Card', 'Sci-Fi Military', '', 'ProductPageAssets/3ScienceFiction/sf3.jpg', 720.00, 0, 352, 'Classic Science Fiction Tale'),
  (14, 'Foundation', 'Isaac Asimov', 'Sci-Fi', '', 'ProductPageAssets/3ScienceFiction/sf4.jpg', 680.00, 0, 296, 'The Foundation Trilogy Book 1'),
  (15, 'The Martian', 'Andy Weir', 'Science Fiction', '', 'ProductPageAssets/3ScienceFiction/sf5.jpg', 795.00, 0, 384, 'Survival Story on Mars'),
  (16, 'The Shining', 'Stephen King', 'Horror', '', 'ProductPageAssets/4Horror/h1.jpg', 820.00, 0, 447, 'Chilling Hotel Thriller'),
  (17, 'Dracula', 'Bram Stoker', 'Gothic Horror', '', 'ProductPageAssets/4Horror/h2.jpg', 560.00, 0, 418, 'Classic Vampire Tale'),
  (18, 'The Exorcist', 'William Peter Blatty', 'Horror', '', 'ProductPageAssets/4Horror/h3.jpg', 640.00, 0, 400, 'Supernatural Masterpiece'),
  (19, 'Frankenstein', 'Mary Shelley', 'Gothic Fiction', '', 'ProductPageAssets/4Horror/h4.jpg', 530.00, 0, 288, 'Dark Scientific Tale'),
  (20, 'It', 'Stephen King', 'Horror', '', 'ProductPageAssets/4Horror/h5.jpg', 999.00, 0, 1138, 'Iconic Supernatural Classic'),
  (21, 'Me Before You', 'Jojo Moyes', 'Romance', '', 'ProductPageAssets/5Romance/r1.jpg', 650.00, 0, 480, 'Heartwarming Love Story'),
  (22, 'The Notebook', 'Nicholas Sparks', 'Romance', '', 'ProductPageAssets/5Romance/r2.jpg', 570.00, 0, 214, 'Touching Love Story'),
  (23, 'To All the Boys I''ve Loved Before', 'Jenny Han', 'Romance', '', 'ProductPageAssets/5Romance/r3.jpg', 630.00, 0, 355, 'Teen Love Favorite'),
  (24, 'P.S. I Love You', 'Cecelia Ahern', 'Romance', '', 'ProductPageAssets/5Romance/r4.jpg', 580.00, 0, 470, 'Heartfelt Story of Love and Loss'),
  (25, 'The Time Traveler''s Wife', 'Audrey Niffenegger', 'Romance/Sci-Fi', '', 'ProductPageAssets/5Romance/r5.jpg', 690.00, 0, 528, 'Time-Bending Love Story')
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  author = VALUES(author),
  genre = VALUES(genre),
  description = VALUES(description),
  image = VALUES(image),
  price = VALUES(price),
  discount = VALUES(discount),
  pages = VALUES(pages),
  collection = VALUES(collection);
