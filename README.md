# Sistem Menaxhimi i Klinikës Mjekësore

**Lënda:** Programim në Web — PW2526  
**Fakulteti:** Shkencave të Natyrës, Universiteti i Tiranës  
**Grupi:** 3 studente

---

## Stack Teknologjik

- **Frontend:** HTML5, CSS3 (custom), JavaScript, jQuery, AJAX
- **Backend:** PHP (pure, pa framework)
- **Databazë:** MySQL (3NF)
- **Autentifikim:** PHP Sessions + Google OAuth2

---

## Instalimi

### Kërkesat
- PHP 8.x
- MySQL 5.7+
- XAMPP / WAMP / LAMP
- Composer (për PHPMailer dhe Google Client)

### Hapat

1. Clone projektin:
```bash
git clone https://github.com/username/clinic.git
cd clinic
```

2. Instalo varësitë:
```bash
composer require phpmailer/phpmailer
composer require google/apiclient
```

3. Krijo databazën:
```sql
CREATE DATABASE clinic_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Importo skemën:
```bash
mysql -u root -p clinic_db < database/clinic.sql
```

5. Konfiguro `.env`:
```bash
cp .env.example .env
# Edito .env me të dhënat tuaja
```

6. Hap në browser:
```
http://localhost/clinic
```

---

## Kredencialet Demo

| Roli | Email | Fjalëkalimi |
|------|-------|-------------|
| Admin | admin@klinika.al | Admin@123 |
| Mjek | doktor@klinika.al | Doctor@123 |
| Pacient | pacient@klinika.al | Patient@123 |

---

## Modulet e Implementuara

- [x] User Authentication (Login/Logout/Register)
- [x] Google OAuth2
- [x] Email Integration (PHPMailer)
- [x] File Upload & Download (receta)
- [x] CRUD (mjekët, shërbimet, oraret, çmimet)
- [x] AJAX Live Search
- [x] CSRF Protection
- [x] SQL Injection Protection (PDO)
- [x] XSS Protection

---

## Struktura e Folderëve

```
clinic/
├── config/          # Konfigurim dhe databazë
├── includes/        # PHP shared (auth, csrf, email, etj.)
├── public/          # Faqet publike (home, login, register, etj.)
├── patient/         # Panel i pacientit
├── doctor/          # Panel i mjekut
├── admin/           # Panel i administratorit
├── api/             # AJAX endpoints
├── assets/          # CSS, JS, imazhe
├── database/        # SQL skema
└── uploads/         # Recetat e ngarkuara (private)
```
