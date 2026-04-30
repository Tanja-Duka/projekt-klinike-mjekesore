<?php
// ============================================================
// api/reserve_ajax.php - Rezervim takimi (AJAX POST)
// ============================================================

// TODO: require_once '../config/config.php'
// TODO: Kontrollo: sesion + rol 'patient' + CSRF token
// TODO: Valido: doctor_id, service_id, appointment_date, time_slot
// TODO: Kontrollo nëse slot-i është i lirë
// TODO: INSERT INTO appointments (patient_id, doctor_id, service_id, date, status='pending')
// TODO: sendConfirmationEmail()
// TODO: echo json_encode(['success' => true, 'appointment_id' => $id])
