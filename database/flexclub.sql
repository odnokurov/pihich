-- FlexClub. В phpMyAdmin выберите пустую базу и импортируйте этот файл.
-- В таблицах ниже используется InnoDB и UTF-8. Скрипт выполняется один раз.
SET NAMES utf8mb4;

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('client','trainer','admin') NOT NULL DEFAULT 'client',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE gyms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trainers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    bio TEXT NULL,
    photo_path VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trainer_gym (
    trainer_id BIGINT UNSIGNED NOT NULL,
    gym_id BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    FOREIGN KEY (gym_id) REFERENCES gyms(id),
    PRIMARY KEY (trainer_id, gym_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    kind ENUM('fixed','custom') NOT NULL,
    duration_months TINYINT UNSIGNED NULL,
    visits_limit SMALLINT UNSIGNED NULL,
    price_kopecks INT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (price_kopecks > 0),
    CHECK ((kind = 'fixed' AND duration_months IS NOT NULL AND duration_months BETWEEN 0 AND 12 AND visits_limit IS NOT NULL AND visits_limit > 0) OR (kind = 'custom' AND duration_months IS NULL AND visits_limit IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    gym_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    duration_months TINYINT UNSIGNED NOT NULL,
    visits_per_month TINYINT UNSIGNED NULL,
    visits_limit SMALLINT UNSIGNED NOT NULL,
    price_kopecks INT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    start_on DATE NULL,
    end_on DATE NULL,
    rejection_reason VARCHAR(500) NULL,
    decided_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (gym_id) REFERENCES gyms(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id),
    FOREIGN KEY (decided_by) REFERENCES users(id),
    INDEX (user_id, status, gym_id, start_on, end_on),
    CHECK (duration_months BETWEEN 0 AND 12 AND visits_limit > 0 AND price_kopecks > 0),
    CHECK (visits_per_month IS NULL OR (visits_per_month BETWEEN 1 AND 24 AND duration_months >= 1 AND visits_limit = visits_per_month * duration_months)),
    UNIQUE (id, user_id),
    CHECK ((status = 'approved' AND start_on IS NOT NULL AND end_on > start_on AND end_on IS NOT NULL) OR (status <> 'approved' AND start_on IS NULL AND end_on IS NULL)),
    CHECK (status <> 'rejected' OR (rejection_reason IS NOT NULL AND CHAR_LENGTH(TRIM(rejection_reason)) > 0)),
    CHECK ((status = 'pending' AND decided_by IS NULL) OR (status <> 'pending' AND decided_by IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE training_slots (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    trainer_id BIGINT UNSIGNED NOT NULL,
    gym_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    capacity SMALLINT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    FOREIGN KEY (gym_id) REFERENCES gyms(id),
    CHECK (ends_at > starts_at),
    CHECK (capacity > 0),
    INDEX (gym_id, starts_at),
    FOREIGN KEY (trainer_id, gym_id) REFERENCES trainer_gym(trainer_id, gym_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    slot_id BIGINT UNSIGNED NOT NULL,
    membership_id BIGINT UNSIGNED NOT NULL,
    status ENUM('booked','cancelled','attended','missed') NOT NULL DEFAULT 'booked',
    attended_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES training_slots(id),
    UNIQUE (user_id, slot_id),
    INDEX (slot_id, status),
    INDEX (membership_id, status),
    FOREIGN KEY (membership_id, user_id) REFERENCES membership_requests(id, user_id),
    CHECK ((status = 'attended' AND attended_at IS NOT NULL) OR (status <> 'attended' AND attended_at IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_threads (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    trainer_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    UNIQUE (user_id, trainer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    thread_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    body VARCHAR(2000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES chat_threads(id),
    FOREIGN KEY (sender_id) REFERENCES users(id),
    CHECK (CHAR_LENGTH(TRIM(body)) > 0),
    INDEX (thread_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gyms (name, address) VALUES ('Алтайская', 'Томск, ул. Алтайская, 159/3'), ('Киевская', 'Томск, ул. Киевская, 139');
INSERT INTO plans (name, kind, duration_months, visits_limit, price_kopecks) VALUES
    ('Разовое посещение', 'fixed', 0, 1, 30000),
    ('4 тренировки в месяц', 'fixed', 1, 4, 100000),
    ('8 тренировок в месяц', 'fixed', 1, 8, 350000),
    ('12 тренировок в месяц', 'fixed', 1, 12, 500000),
    ('8 тренировок за 2 месяца', 'fixed', 2, 8, 550000),
    ('8 тренировок за 3 месяца', 'fixed', 3, 8, 800000),
    ('Настраиваемый абонемент', 'custom', NULL, NULL, 45000);
