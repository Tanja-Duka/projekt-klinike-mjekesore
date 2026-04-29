DROP DATABASE IF EXISTS clinic_db;

CREATE DATABASE clinic_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE clinic_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('patient','doctor','admin') NOT NULL,
    phone VARCHAR(20) NULL,
    is_active TINYINT(1) DEFAULT 1,
    google_id VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Vetëm për doctor
    specialization VARCHAR(100) NULL,
    bio TEXT NULL,
    photo_path VARCHAR(255) NULL,
    consultation_fee DECIMAL(8,2) NULL,

    -- Vetëm për patient
    date_of_birth DATE NULL,
    blood_type VARCHAR(5) NULL,
    address VARCHAR(255) NULL,
    emergency_contact VARCHAR(100) NULL,

    CONSTRAINT pk_users PRIMARY KEY (id),
    CONSTRAINT uq_users_email UNIQUE (email)
);

CREATE TABLE services (
    id INT AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(8,2) NOT NULL,
    icon VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,

    CONSTRAINT pk_services PRIMARY KEY (id),
    CONSTRAINT uq_services_name UNIQUE (name)
);

CREATE TABLE appointments (
    id INT AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    time_slot TIME NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_appointments PRIMARY KEY (id),

    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_appointments_doctor
        FOREIGN KEY (doctor_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_appointments_service
        FOREIGN KEY (service_id)
        REFERENCES services(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_prescriptions PRIMARY KEY (id),

    CONSTRAINT fk_prescriptions_appointment
        FOREIGN KEY (appointment_id)
        REFERENCES appointments(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_prescriptions_doctor
        FOREIGN KEY (doctor_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_prescriptions_patient
        FOREIGN KEY (patient_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,

    CONSTRAINT pk_schedules PRIMARY KEY (id),

    CONSTRAINT fk_schedules_doctor
        FOREIGN KEY (doctor_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT uq_schedule_doctor_day 
        UNIQUE (doctor_id, day_of_week)
);

CREATE TABLE contact_queries (
    id INT AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read','resolved') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_contact_queries PRIMARY KEY (id)
);

CREATE TABLE session_logs (
    id INT AUTO_INCREMENT,
    user_id INT NOT NULL,
    role VARCHAR(20) NOT NULL,
    action ENUM('login','logout') NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_session_logs PRIMARY KEY (id),

    CONSTRAINT fk_session_logs_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

INSERT INTO users
(name, email, password_hash, role, phone, specialization, bio, photo_path, consultation_fee, date_of_birth, blood_type, address, emergency_contact)
VALUES
('Admin Klinika', 'admin@klinika.al', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0690000000', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),

('Dr. Arben Hoxha', 'doktor@klinika.al', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '0691111111', 'Kardiologji', 'Mjek specialist kardiolog.', 'assets/images/doctors/arben.jpg', 3000.00, NULL, NULL, NULL, NULL),

('Dr. Ilir Kola', 'doktor2@klinika.al', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', '0692222222', 'Neurologji', 'Mjek specialist neurolog.', 'assets/images/doctors/ilir.jpg', 3500.00, NULL, NULL, NULL, NULL),

('Elena Doci', 'pacient@klinika.al', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '0693333333', NULL, NULL, NULL, NULL, '2000-04-12', 'A+', 'Tirane', '0699999999'),

('Mario Leka', 'pacient2@klinika.al', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '0694444444', NULL, NULL, NULL, NULL, '1998-09-20', 'O+', 'Durres', '0688888888');

INSERT INTO services
(name, description, category, price, icon)
VALUES
('Kardiologji', 'Kontroll zemre dhe konsultë me mjek kardiolog.', 'Specialist', 3000.00, 'heart'),
('Neurologji', 'Kontroll neurologjik dhe vlerësim specialistik.', 'Specialist', 3500.00, 'brain'),
('Pediatri', 'Kontroll për fëmijë.', 'Specialist', 2500.00, 'child'),
('Ortopedi', 'Kontroll për kocka dhe kyçe.', 'Specialist', 3000.00, 'bone'),
('Dermatologji', 'Kontroll për lëkurën.', 'Specialist', 2800.00, 'skin'),
('Laborator', 'Analiza laboratorike.', 'Analiza', 1500.00, 'flask'),
('Radiologji', 'Ekografi dhe imazheri.', 'Diagnostikim', 4000.00, 'xray');

INSERT INTO schedules
(doctor_id, day_of_week, start_time, end_time, is_available)
VALUES
(2, 'Monday', '09:00:00', '14:00:00', 1),
(2, 'Tuesday', '09:00:00', '14:00:00', 1),
(3, 'Wednesday', '10:00:00', '15:00:00', 1),
(3, 'Thursday', '10:00:00', '15:00:00', 1);

INSERT INTO appointments
(patient_id, doctor_id, service_id, appointment_date, time_slot, status, notes)
VALUES
(4, 2, 1, '2026-05-10', '10:00:00', 'pending', 'Kontroll fillestar.'),
(5, 3, 2, '2026-05-11', '11:30:00', 'confirmed', 'Kontroll neurologjik.');

INSERT INTO prescriptions
(appointment_id, doctor_id, patient_id, file_path)
VALUES
(1, 2, 4, 'uploads/prescriptions/rec1.pdf'),
(2, 3, 5, 'uploads/prescriptions/rec2.pdf');

INSERT INTO contact_queries
(name, email, subject, message, status)
VALUES
('Ana Test', 'ana@test.com', 'Pyetje per kliniken', 'Dua te rezervoj nje takim.', 'unread');

INSERT INTO session_logs
(user_id, role, action, ip_address)
VALUES
(1, 'admin', 'login', '127.0.0.1'),
(2, 'doctor', 'login', '127.0.0.1'),
(4, 'patient', 'login', '127.0.0.1');