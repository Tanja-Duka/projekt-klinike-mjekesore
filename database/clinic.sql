DROP DATABASE IF EXISTS clinic_db;

CREATE DATABASE clinic_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,

    CONSTRAINT pk_roles PRIMARY KEY (role_id),
    CONSTRAINT uq_roles_role_name UNIQUE (role_name)
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,

    CONSTRAINT pk_users PRIMARY KEY (user_id),
    CONSTRAINT uq_users_email UNIQUE (email),

    CONSTRAINT fk_users_roles
        FOREIGN KEY (role_id)
        REFERENCES roles(role_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE admin (
    admin_id INT,
    a_name VARCHAR(100) NOT NULL,
    a_surname VARCHAR(100) NOT NULL,

    CONSTRAINT pk_admin PRIMARY KEY (admin_id),

    CONSTRAINT fk_admin_users
        FOREIGN KEY (admin_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE specialties (
    specialty_id INT AUTO_INCREMENT,
    specialty_name VARCHAR(100) NOT NULL,

    CONSTRAINT pk_specialties PRIMARY KEY (specialty_id),
    CONSTRAINT uq_specialties_specialty_name UNIQUE (specialty_name)
);

CREATE TABLE doctors (
    doctor_id INT,
    d_name VARCHAR(100) NOT NULL,
    d_surname VARCHAR(100) NOT NULL,
    specialty_id INT NOT NULL,
    admin_id INT NOT NULL,

    CONSTRAINT pk_doctors PRIMARY KEY (doctor_id),

    CONSTRAINT fk_doctors_users
        FOREIGN KEY (doctor_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_doctors_specialties
        FOREIGN KEY (specialty_id)
        REFERENCES specialties(specialty_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_doctors_admin
        FOREIGN KEY (admin_id)
        REFERENCES admin(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

CREATE TABLE patients (
    patient_id INT,
    p_name VARCHAR(100) NOT NULL,
    p_surname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,

    CONSTRAINT pk_patients PRIMARY KEY (patient_id),

    CONSTRAINT uq_patients_phone UNIQUE (phone),

    CONSTRAINT fk_patients_users
        FOREIGN KEY (patient_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
CREATE TABLE services (
    service_id INT AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,

    CONSTRAINT pk_services PRIMARY KEY (service_id),
    CONSTRAINT uq_services_service_name UNIQUE (service_name)
);
CREATE TABLE schedules (
    schedules_id INT AUTO_INCREMENT,
    weekday ENUM(
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    ) NOT NULL,
    start_hour TIME NOT NULL,
    end_hour TIME NOT NULL,
    doctor_id INT NOT NULL,
    admin_id INT NOT NULL,

    CONSTRAINT pk_schedules PRIMARY KEY (schedules_id),

    CONSTRAINT fk_schedules_doctors
        FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_schedules_admin
        FOREIGN KEY (admin_id)
        REFERENCES admin(admin_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_schedules_doctor_weekday_time
        UNIQUE (doctor_id, weekday, start_hour, end_hour)
);

CREATE TABLE appointments (
    app_id INT AUTO_INCREMENT,
    appointment_date DATE NOT NULL,
    appointment_hour TIME NOT NULL,
    status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    service_id INT NOT NULL,

    CONSTRAINT pk_appointments PRIMARY KEY (app_id),

    CONSTRAINT fk_appointments_patients
        FOREIGN KEY (patient_id)
        REFERENCES patients(patient_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_appointments_doctors
        FOREIGN KEY (doctor_id)
        REFERENCES doctors(doctor_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_appointments_services
        FOREIGN KEY (service_id)
        REFERENCES services(service_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_appointments_doctor_datetime
        UNIQUE (doctor_id, appointment_date, appointment_hour)
);

CREATE TABLE medical_records (
    history_id INT AUTO_INCREMENT,
    diagnosis TEXT NOT NULL,
    pdf_file_path VARCHAR(255) NOT NULL,
    app_id INT NOT NULL,

    CONSTRAINT pk_medical_records PRIMARY KEY (history_id),

    CONSTRAINT uq_medical_records_app UNIQUE (app_id),

    CONSTRAINT fk_medical_records_appointments
        FOREIGN KEY (app_id)
        REFERENCES appointments(app_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);



INSERT INTO roles (role_name) VALUES
('admin'),
('doctor'),
('patient');


INSERT INTO users (email, password, role_id) VALUES
('admin@klinika.al', '123456', 1),
('doktor1@klinika.al', '123456', 2),
('doktor2@klinika.al', '123456', 2),
('pacient1@klinika.al', '123456', 3),
('pacient2@klinika.al', '123456', 3);

INSERT INTO admin (admin_id, a_name, a_surname) VALUES
(1, 'Igor', 'Rada');

INSERT INTO specialties (specialty_name) VALUES
('Kardiologji'),
('Neurologji'),
('Pediatri');

INSERT INTO doctors (doctor_id, d_name, d_surname, specialty_id, admin_id) VALUES
(2, 'Arben', 'Hoxha', 1, 1),
(3, 'Ilir', 'Kola', 2, 1);

INSERT INTO patients (patient_id, p_name, p_surname, phone) VALUES
(4, 'Elena', 'Doci', '0691111111'),
(5, 'Mario', 'Leka', '0692222222');

INSERT INTO services (service_name, description, price) VALUES
('Kontroll Kardiologjik', 'Kontroll zemre', 3000),
('Kontroll Neurologjik', 'Kontroll truri', 3500),
('Kontroll Pediatrik', 'Kontroll femije', 2500);

INSERT INTO schedules (weekday, start_hour, end_hour, doctor_id, admin_id) VALUES
('Monday', '09:00:00', '14:00:00', 2, 1),
('Tuesday', '10:00:00', '15:00:00', 3, 1);

INSERT INTO appointments 
(appointment_date, appointment_hour, status, patient_id, doctor_id, service_id)
VALUES
('2026-05-10', '10:00:00', 'pending', 4, 2, 1),
('2026-05-11', '11:30:00', 'confirmed', 5, 3, 2);

INSERT INTO medical_records (diagnosis, pdf_file_path, app_id) VALUES
('Gjendje e mire', 'files/rec1.pdf', 1),
('Duhet kontroll shtese', 'files/rec2.pdf', 2);

SELECT * FROM users;
SELECT * FROM doctors;
SELECT * FROM appointments;