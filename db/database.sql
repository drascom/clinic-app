CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  name TEXT NULL,
  surname TEXT NULL,
  username TEXT  NULL,
  password TEXT NOT NULL,
  role TEXT DEFAULT 'guest',
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  agency_id INTEGER,
  reset_token TEXT NULL,
  reset_expiry DATETIME NULL,

  phone TEXT NULL,
  is_active INTEGER DEFAULT 0,
  created_by INTEGER DEFAULT 1,
  updated_by INTEGER NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE surgeries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT NOT NULL,
  notes TEXT,
  status TEXT DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'confirmed', 'completed', 'canceled') OR status IS NULL),
  predicted_grafts_count INTEGER,
  current_grafts_count INTEGER,
  room_id INTEGER,
  patient_id INTEGER NOT NULL,
  is_recorded BOOLEAN DEFAULT FALSE,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  created_by INTEGER DEFAULT 1,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO surgeries (id, date, notes, status, predicted_grafts_count, current_grafts_count, room_id, patient_id, is_recorded, updated_by, created_at, updated_at, created_by) VALUES (1, '2025-06-15', 'test', 'scheduled', NULL, NULL, 1, 1, 1, 1, '2025-06-15 09:38:22', '2025-06-15 09:38:22', 1);
INSERT INTO surgeries (id, date, notes, status, predicted_grafts_count, current_grafts_count, room_id, patient_id, is_recorded, updated_by, created_at, updated_at, created_by) VALUES (2, '2025-07-01', 'Follow-up for Jane Doe', 'scheduled', 2000, 0, 2, 2, 0, 1, '2025-06-21 10:10:00', '2025-06-21 10:10:00', 1);
INSERT INTO surgeries (id, date, notes, status, predicted_grafts_count, current_grafts_count, room_id, patient_id, is_recorded, updated_by, created_at, updated_at, created_by) VALUES (3, '2025-07-10', 'Initial consultation for John Smith', 'scheduled', 2500, 0, 3, 3, 0, 1, '2025-06-21 10:15:00', '2025-06-21 10:15:00', 1);

CREATE TABLE appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER,
    patient_id INTEGER NOT NULL ,
    appointment_date DATE NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    procedure_id INTEGER,
    appointment_type TEXT DEFAULT 'consultation' CHECK (appointment_type IN ('consultation', 'treatment') OR appointment_type IS NULL),
    consultation_type TEXT DEFAULT 'face-to-face' CHECK (consultation_type IN ('face-to-face', 'video-to-video') OR consultation_type IS NULL),
    notes TEXT,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (procedure_id) REFERENCES procedures(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );

CREATE TABLE patients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT,
  dob TEXT,
  phone TEXT,
  city TEXT,
  gender TEXT DEFAULT 'N/A' CHECK (gender IN ('N/A', 'Male', 'Female', 'Transgender') OR gender IS NULL),
  avatar TEXT,
  occupation TEXT,
  agency_id INTEGER NOT NULL,
  photo_album_id INTEGER,
  updated_by INTEGER NULL,
  created_by INTEGER DEFAULT 1,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO patients (id, name, email, dob, phone, city, gender, avatar, occupation, agency_id, photo_album_id, created_by, created_at, updated_at) VALUES (1, 'emin colak', 'drascom07@gmail.com', '2025-06-12', '432424', 'Barnet', 'Male', NULL, '', 1, NULL, 1, '2025-06-15 09:09:48', '2025-06-15 09:09:48');
INSERT INTO patients (id, name, email, dob, phone, city, gender, avatar, occupation, agency_id, photo_album_id, created_by, created_at, updated_at) VALUES (2, 'Jane Doe', 'jane.doe@example.com', '1990-01-01', '1112223333', 'London', 'Female', NULL, 'Engineer', 2, NULL, 2, '2025-06-21 10:00:00', '2025-06-21 10:00:00');
INSERT INTO patients (id, name, email, dob, phone, city, gender, avatar, occupation, agency_id, photo_album_id, created_by, created_at, updated_at) VALUES (3, 'John Smith', 'john.smith@example.com', '1985-05-10', '4445556666', 'Manchester', 'Male', NULL, 'Designer', 3, NULL, 3, '2025-06-21 10:05:00', '2025-06-21 10:05:00');


CREATE TABLE photo_album_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  created_by INTEGER DEFAULT 1,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE patient_photos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  patient_id INTEGER NOT NULL,
  photo_album_type_id INTEGER,
  file_path TEXT,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  created_by INTEGER DEFAULT 1,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE agencies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  created_by INTEGER DEFAULT 1,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    role TEXT NOT NULL,
    agency_id INTEGER,
    token TEXT NOT NULL UNIQUE,
    status TEXT DEFAULT 'pending',
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    used_at TEXT,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );


CREATE TABLE IF NOT EXISTS interview_invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    interview_date DATE NOT NULL,
    interview_time TIME NOT NULL,
    meeting_platform TEXT NOT NULL,
    meeting_link TEXT NOT NULL,
    interview_duration TEXT NOT NULL DEFAULT '20 minutes',
    video_upload_link TEXT NULL,
    upload_password TEXT DEFAULT 'LIV-HSH',
    sent_at TEXT DEFAULT CURRENT_TIMESTAMP,
    sent_by INTEGER NOT NULL,
    email_status TEXT DEFAULT 'sent',
    status TEXT DEFAULT 'draft',
    notes TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_by INTEGER NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
-- Room Management Tables
CREATE TABLE rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    type TEXT,
    is_active INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );

CREATE TABLE room_reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    surgery_id INTEGER NOT NULL,
    reserved_date DATE NOT NULL,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(room_id, reserved_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );
CREATE TABLE closed_days (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL UNIQUE,
    reason TEXT,
    closed_by_user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (closed_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT  NULL,
    email TEXT  NULL,
    location TEXT NOT NULL,
    position_applied TEXT NULL,
    staff_type TEXT DEFAULT 'candidate' CHECK (staff_type IN ('candidate', 'staff') OR staff_type IS NULL),
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );

CREATE TABLE IF NOT EXISTS staff_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    speciality TEXT,
    experience_level TEXT CHECK (experience_level IN ('entry level', 'junior', 'mid-level', 'senior', 'expert') OR experience_level IS NULL),
    current_company TEXT,
    linkedin_profile TEXT,
    source TEXT CHECK (source IN ('Website', 'LinkedIn', 'Indeed', 'Referral', 'Agency', 'Other') OR source IS NULL),
    salary_expectation INTEGER DEFAULT 0,
    daily_fee INTEGER NOT NULL DEFAULT 0,
    willing_to_relocate INTEGER NOT NULL DEFAULT 0,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );

CREATE TABLE IF NOT EXISTS staff_availability (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    available_on DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'available' CHECK (status IN ('full_day', 'half_day','unavailable')),
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (staff_id, available_on),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );



-- New table for surgery-technician assignments
CREATE TABLE surgery_staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    surgery_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (surgery_id, staff_id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );

CREATE TABLE IF NOT EXISTS procedures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    is_active INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
  );

CREATE TABLE settings (
  key TEXT PRIMARY KEY UNIQUE,
  value TEXT,
  created_by INTEGER DEFAULT 1,
  updated_by INTEGER NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('Pre-Surgery', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('Post-Surgery', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('Follow-up', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('1. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('2. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('3. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('4. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('5. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('6. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('7. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('8. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('9. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('10. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('11. Month', 1, datetime('now'), datetime('now'), 1);
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at, created_by) VALUES ('12. Month', 1, datetime('now'), datetime('now'), 1);




INSERT INTO users (id,name,surname,email, username, password,role, created_at, updated_at, agency_id, is_active, created_by) VALUES (1,'Ayhan','Colak','drayhan@msn.com', 'Admin', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','admin', datetime('now'), datetime('now'),1,1, 1);
INSERT INTO users (id,name,surname,email, username, password,role, created_at, updated_at, agency_id, is_active, created_by) VALUES (2,'Want','Hair','agent@example.com', 'Want Hair', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','agent', datetime('now'), datetime('now'),2,1, 1);
INSERT INTO users (id,name,surname,email, username, password,role, created_at, updated_at, agency_id, is_active, created_by) VALUES (3,'Ebru','Tok','ebru.tok@livharleystreet.co.uk', 'EBRU', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','admin', datetime('now'), datetime('now'),1,1, 1);
INSERT INTO users (id,name,surname,email, username, password,role, created_at, updated_at, agency_id, is_active, created_by) VALUES (4,'Smy','Yuksel','smy@livharleystreet.co.uk', 'SMY', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','admin', datetime('now'), datetime('now'),1,1, 1);
INSERT INTO users (id,name,surname,email, username, password,role, created_at, updated_at, agency_id, is_active, created_by) VALUES (5,'Asli','Batten','asli.ozturk@mlpcare.com', 'ASLI ', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','editor', datetime('now'), datetime('now'),1,1, 1);
INSERT INTO users (id,name,surname,email, username, password,role, created_at, updated_at, agency_id, is_active, created_by) VALUES (6,'tech','tech','tech@example.com', 'Technician', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','technician', datetime('now'), datetime('now'),3,1, 1);




INSERT INTO settings (key, value, created_by) VALUES ('spreadsheet_id', '1mP20et8Pe_RMQvEC-ra2mXi9aoAtTwK_jsMwcUn9tt0', 1);
INSERT INTO settings (key, value, created_by) VALUES ('cache_duration', '300', 1);
INSERT INTO settings (key, value, created_by) VALUES ('cell_range', 'A1:I30', 1);




-- Insert sample agencies for testing
INSERT INTO agencies (name, updated_by, created_at, updated_at, created_by) VALUES ('Hospital', 1, datetime('now'), datetime('now'), 1);
INSERT INTO agencies (name, updated_by, created_at, updated_at, created_by) VALUES ('Want Hair', 1, datetime('now'), datetime('now'), 1);
INSERT INTO agencies (name, updated_by, created_at, updated_at, created_by) VALUES ('Other Agency', 1, datetime('now'), datetime('now'), 1);



-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_surgery_staff_surgery_id ON surgery_staff(surgery_id);
CREATE INDEX IF NOT EXISTS idx_surgery_staff_staff_id ON surgery_staff(staff_id);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_room_reservations_room_id ON room_reservations(room_id);
CREATE INDEX IF NOT EXISTS idx_room_reservations_surgery_id ON room_reservations(surgery_id);
CREATE INDEX IF NOT EXISTS idx_room_reservations_date ON room_reservations(reserved_date);
CREATE INDEX IF NOT EXISTS idx_rooms_active ON rooms(is_active);
CREATE INDEX IF NOT EXISTS idx_staff_availability_staff_id ON staff_availability(staff_id);
CREATE INDEX IF NOT EXISTS idx_staff_availability_date ON staff_availability(available_on);
CREATE INDEX IF NOT EXISTS idx_staff_availability_status ON staff_availability(status);
CREATE INDEX IF NOT EXISTS idx_staff_active ON staff(is_active);

-- Indexes for interview_invitations table
CREATE INDEX IF NOT EXISTS idx_interview_invitations_staff_id ON interview_invitations(staff_id);
CREATE INDEX IF NOT EXISTS idx_interview_invitations_date ON interview_invitations(interview_date);
CREATE INDEX IF NOT EXISTS idx_interview_invitations_sent_by ON interview_invitations(sent_by);
CREATE INDEX IF NOT EXISTS idx_interview_invitations_sent_at ON interview_invitations(sent_at);



-- Insert sample rooms for testing
INSERT INTO rooms (id,name, type, is_active, updated_by, created_by) VALUES
(1,'Consultation',  'consultation', 1, 1, 1),
(2,'Cosmetology',  'treatment', 1, 1, 1),
(3,'Surgery 1',  'surgery', 1, 1, 1),
(4,'Surgery 2',  'surgery', 0, 1, 1),
(5,'Surgery 3',  'surgery', 0, 1, 1);

-- Insert demo staff 

-- Insert default procedures
INSERT INTO procedures (name, is_active, updated_by, created_by) VALUES
('Consultation', 1, 1, 1),
('Botox', 1, 1, 1),
('Dermal Fillers', 1, 1, 1),
('PRP (Platelet-Rich Plasma)', 1, 1, 1),
('Microneedling', 1, 1, 1),
('HydraFacial', 1, 1, 1),
('Chemical Peel', 1, 1, 1),
('Laser Hair Removal', 1, 1, 1),
('Skin Rejuvenation', 1, 1, 1),
('Carbon Laser Peel', 1, 1, 1),
('Mesotherapy', 1, 1, 1),
('Facial Cleansing', 1, 1, 1),
('Radiofrequency Skin Tightening', 1, 1, 1),
('Cryolipolysis (Fat Freezing)', 1, 1, 1),
('Ultherapy', 1, 1, 1),
('LED Light Therapy', 1, 1, 1),
('OxyGeneo Facial', 1, 1, 1),
('Hollywood Peel', 1, 1, 1),
('Aqualyx Fat Dissolving', 1, 1, 1),
('Carboxytherapy', 1, 1, 1),
('Lip Augmentation', 1, 1, 1),
('Jawline Contouring', 1, 1, 1);



-- Insert sample staff from job_candidates-export.json
INSERT INTO staff (name, email, phone, position_applied, location, staff_type,updated_by, created_by) VALUES ('esra bilen', 'esrabilen07@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type,updated_by, created_by) VALUES ('ferhat - emine demirtas', 'tahirferhatdemirtas@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('goktug gokalp', 'goktuggokalp34@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('irem cengiz', 'iremcngc@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('muhammed arici', 'muhammetposof28@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('ali emre dogan', 'aedogan.saglik@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('naz ilhan', 'nazilhan1040@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('nesibe idil karpuz', 'n.idilkrp@icloud.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('onur meric bugan', 'onurmeric03@icloud.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('sametcan balci', 'samet42550.97@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('zelal celikten', 'zelalcelikten@gmail.com', 12345678, 'Hair Transplant Technician', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('jhon', 'test@rser.fds', '324242', 'csdsad', 'turkiye', 'candidate', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Shefiu', '07508400686', 07508400686, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Phandira', 12345678, 07424722738, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Eniye','07424722738', 07405497373, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Nava', '07435525455', 07435525455, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Chandu', '07435525358', 07435525358, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Maryam', '07775541099', 07775541099, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Milena', '07857273383',07857273383, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Mahsa – Zahra', '07914262872',07914262872, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Beverly', '07775434126',07775434126, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Claduio', '00393341817614',00393341817614, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Sahnaz', '07404324662',07404324662, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Sravani', '07508858512',07508858512, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Sagun Khadka', '07380576839', 07380576839, 'senior', 'UK', 'staff', 1, 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by, created_by) VALUES ('Monisha', '07436422647',07436422647, 'senior', 'UK', 'staff', 1, 1);

CREATE TABLE IF NOT EXISTS emails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uid TEXT NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    message_id TEXT UNIQUE,
    subject TEXT,
    from_address TEXT NOT NULL,
    from_name TEXT,
    to_address TEXT,
    cc_address TEXT,
    bcc_address TEXT,
    reply_to_address TEXT,
    date_received INTEGER NOT NULL,
    body TEXT,
    is_read BOOLEAN DEFAULT 0,
    folder TEXT DEFAULT 'INBOX',
    is_active BOOLEAN DEFAULT 1,
    is_draft BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
  );
CREATE TABLE user_email_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    email_address VARCHAR(255) NOT NULL,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INTEGER NOT NULL,
    smtp_user VARCHAR(255) NOT NULL,
    smtp_pass VARCHAR(255) NOT NULL,
    smtp_secure VARCHAR(10) NOT NULL,
    imap_host VARCHAR(255) NOT NULL,
    imap_user VARCHAR(255) NOT NULL,
    imap_pass VARCHAR(255) NOT NULL,
    created_by INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
  );
-- INSERT INTO user_email_settings (user_id, email_address, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, imap_host, imap_user, imap_pass) VALUES (1, 'contact@livharleystreet.co.uk', 'gukm1005.siteground.biz', 465, 'ebru.tok@livharleystreet.co.uk', '051224..Uk', 'ssl', 'gukm1005.siteground.biz', 'ebru.tok@livharleystreet.co.uk', '051224..Uk');
INSERT INTO user_email_settings (user_id, email_address, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, imap_host, imap_user, imap_pass, created_by) VALUES (1, 'ayhan@livharleystreet.co.uk', 'gukm1005.siteground.biz', 465, 'ayhan@livharleystreet.co.uk', 'Doktor2024@', 'ssl', 'gukm1005.siteground.biz', 'ayhan@livharleystreet.co.uk', 'Doktor2024@', 1);
INSERT INTO user_email_settings (user_id, email_address, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, imap_host, imap_user, imap_pass, created_by) VALUES (3, 'ebru.tok@livharleystreet.co.uk', 'gukm1005.siteground.biz', 465, 'ebru.tok@livharleystreet.co.uk', '051224..Uk', 'ssl', 'gukm1005.siteground.biz', 'ebru.tok@livharleystreet.co.uk', '051224..Uk', 1);
INSERT INTO user_email_settings (user_id, email_address, smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, imap_host, imap_user, imap_pass, created_by) VALUES (5, 'contact@livharleystreet.co.uk', 'gukm1005.siteground.biz', 465, 'contact@livharleystreet.co.uk', '051224..Uk', 'ssl', 'gukm1005.siteground.biz', 'contact@livharleystreet.co.uk', '051224..Uk', 1);

CREATE TABLE IF NOT EXISTS email_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    mime_type TEXT,
    size INTEGER,
    file_path TEXT,
    email_uid INTEGER,
    part_index TEXT,
    created_by INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
  );

CREATE TABLE IF NOT EXISTS `messages` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `sender_id` INTEGER NOT NULL,
  `receiver_id` INTEGER,
  `related_table` TEXT,
  `patient_id` INTEGER,
  `message` TEXT NOT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_read` INTEGER NOT NULL DEFAULT 0,
  `created_by` INTEGER DEFAULT 1,
  `updated_by` INTEGER,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS message_reactions (
`id` INTEGER PRIMARY KEY AUTOINCREMENT, 
`message_id` INTEGER NOT NULL, 
`user_id` INTEGER NOT NULL, 
`emoji_code` VARCHAR(255) NOT NULL, 
`timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
`created_by` INTEGER DEFAULT 1,
`updated_by` INTEGER,
UNIQUE (message_id, user_id),
FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);


-- Messages for Patient 1 (Emin Colak) with Agent (receiver_id 2)
INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`, `created_by`) VALUES
(1, 2, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Hi Agent, I have a question about my upcoming appointment.', 0, 1),
(2, 1, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Certainly, how can I help you, Emin?', 0, 1),
(1, 2, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'I need to confirm the time for my surgery on the 15th.', 0, 1),
(2, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Your surgery on June 15th is confirmed for 9:00 AM. Room 1.', 0, 1),
(1, 2, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Thank you for the confirmation!', 1, 1),
(2, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'You are welcome. Is there anything else?', 1, 1),
(1, 2, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'No, that is all for now. Have a good day.', 1, 1),
(2, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'You too, Emin. See you soon.', 1, 1);

-- -- Messages for Patient 2 (Jane Doe) with Agent (receiver_id 2)
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Hello, I am Jane Doe. I would like to schedule a follow-up.', 0),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Hi Jane, I see you have a follow-up scheduled for July 1st. Is that correct?', 0),
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Yes, that is correct. I just wanted to make sure everything is in order.', 0),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'All details are confirmed. We look forward to seeing you.', 0),
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Great, thank you!', 1),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'My pleasure. Let me know if you need anything else.', 1),
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Will do. Thanks again!', 1),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'You are most welcome.', 1);

-- -- Messages for Patient 3 (John Smith) with Agent (receiver_id 2)
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'Hi, this is John Smith. I am interested in a consultation.', 0),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'Hello John. We have an initial consultation scheduled for July 10th. Does that work for you?', 0),
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'Yes, that date works perfectly. What time is it?', 0),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'It is at 11:00 AM in Consultation Room. Please arrive 15 minutes early.', 0),
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'Understood. Thank you for the information.', 1),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'You are welcome. We will send a reminder email closer to the date.', 1),
-- (1, 2, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'Perfect. Looking forward to it.', 1),
-- (2, 1, '[{"table_name":"patients","field_name":"name","id":3}]', 3, 'See you then!', 1);

-- -- Messages to Technician (receiver_id=6, table_name:patient,id:1) talk about patient and treatments
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (1, 6, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Technician, please review Emin Colak''s treatment plan for next session.', 0),
-- (6, 1, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Understood. I will review Emin Colak''s treatment plan and prepare accordingly.', 0),
-- (1, 6, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Ensure all necessary medications and equipment for his specific treatment are ready.', 0),
-- (6, 1, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Confirmed. All required items for Emin Colak''s treatment will be prepared.', 0),
-- (1, 6, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Great. Let me know if there are any concerns regarding the treatment.', 1),
-- (6, 1, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Will do. Everything seems to be in order for his treatment.', 1),
-- (1, 6, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Thank you for your attention to detail on this treatment.', 1),
-- (6, 1, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'My pleasure. Patient care is our priority.', 1);

-- -- New sample messages for grouping by entity (sender_id = 1, receiver_id = 3)

-- -- Group 1: Patients, entity_id = 1 (Emin Colak)
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (1, 3, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Please check Emin Colak''s latest patient notes.', 0),
-- (3, 1, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'I have reviewed Emin Colak''s notes. All clear.', 0),
-- (1, 3, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Confirm next appointment for Emin Colak.', 0),
-- (3, 1, '[{"table_name":"patients","field_name":"name","id":1}]', 1, 'Emin Colak''s next appointment is scheduled for July 5th.', 0);

-- -- Group 2: Surgeries, entity_id = 2 (Jane Doe's surgery)
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (1, 3, '[{"table_name":"surgeries","field_name":"notes","id":2}]', 2, 'Jane Doe''s surgery on July 1st needs final confirmation.', 0),
-- (3, 1, '[{"table_name":"surgeries","field_name":"notes","id":2}]', 2, 'Confirmed. Jane Doe''s surgery is all set for July 1st.', 0),
-- (1, 3, '[{"table_name":"surgeries","field_name":"notes","id":2}]', 2, 'Please ensure all pre-op documents are signed.', 0),
-- (3, 1, '[{"table_name":"surgeries","field_name":"notes","id":2}]', 2, 'All documents for Jane Doe''s surgery are signed and filed.', 0);

-- -- Group 3: Staff messages
-- -- Messages between sender:1 and receiver:3 talk about patient: 2
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (1, 3, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'Patient Jane Doe needs a follow-up on her recent consultation.', 0),
-- (3, 1, '[{"table_name":"patients","field_name":"name","id":2}]', 2, 'I will check Jane Doe''s file and schedule a call.', 0);
-- -- Messages between sender:1 and receiver:2 talk about staff id:6
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (1, 2, '[{"table_name":"staff","field_name":"name","id":6}]', NULL, 'Technician (ID 6) has updated their availability.', 0),
-- (2, 1, '[{"table_name":"staff","field_name":"name","id":6}]', NULL, 'Noted. I will update the schedule accordingly for Technician (ID 6).', 0);
-- -- Messages between sender:2 and receiver:3 talk about staff id:6
-- INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`) VALUES
-- (2, 3, '[{"table_name":"staff","field_name":"name","id":6}]', NULL, 'Can you confirm Technician (ID 6)''s training completion?', 0),
-- (3, 2, '[{"table_name":"staff","field_name":"name","id":6}]', NULL, 'Yes, Technician (ID 6) completed all modules yesterday.', 0);

-- General broadcast message (receiver_id 0)
INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`, `created_by`) VALUES
(1, 0, NULL, NULL, 'All staff: Please remember to log your daily activities by 5 PM.', 0, 1),
(1, 0, NULL, NULL, 'Important announcement: Clinic will be closed on July 4th for holiday.', 0, 1),
(1, 0, NULL, NULL, 'New protocol for patient intake has been uploaded to the shared drive.', 0, 1),
(1, 0, NULL, NULL, 'Reminder: Annual fire safety drill next Tuesday at 10 AM.', 0, 1),
(1, 0, NULL, NULL, 'Please ensure all patient records are updated by end of week.', 1, 1),
(1, 0, NULL, NULL, 'New stock of medical supplies has arrived. Please check inventory.', 1, 1),
(1, 0, NULL, NULL, 'Team meeting scheduled for Monday at 9 AM in the main conference room.', 1, 1),
(1, 0, NULL, NULL, 'Wishing everyone a productive week!', 1, 1);

-- Messages related to Appointments (hypothetical appointment ID 1, patient ID 1)
INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`, `created_by`) VALUES
(1, 2, '[{"table_name":"appointments","field_name":"appointment_date","id":1}]', 1, 'Appointment for Emin Colak (ID 1) on 2025-07-05 needs confirmation.', 0, 1),
(2, 1, '[{"table_name":"appointments","field_name":"appointment_date","id":1}]', 1, 'Confirmed. Emin Colak''s appointment is all set.', 0, 1);

-- Messages related to Rooms (room ID 4, patient ID 3)
INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`, `created_by`) VALUES
(1, 3, '[{"table_name":"rooms","field_name":"name","id":4}]', 3, 'Consultation Room (ID 4) is reserved for John Smith (ID 3) on July 10th.', 0, 1),
(3, 1, '[{"table_name":"rooms","field_name":"name","id":4}]', 3, 'Noted. Consultation Room (ID 4) reservation confirmed for John Smith.', 0, 1);

-- Messages related to Procedures (procedure ID 1, patient ID 2)
INSERT INTO `messages` (`sender_id`, `receiver_id`, `related_table`, `patient_id`, `message`, `is_read`, `created_by`) VALUES
(1, 5, '[{"table_name":"procedures","field_name":"name","id":1}]', 2, 'Jane Doe (ID 2) is scheduled for a Consultation (ID 1).', 0, 1),
(5, 1, '[{"table_name":"procedures","field_name":"name","id":1}]', 2, 'Understood. I will prepare for Jane Doe''s Consultation.', 0, 1);

-- Message Reactions
-- Message Reactions (message_id values adjusted to match auto-incremented IDs)
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (1, 2, '\u{1F44D}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (2, 3, '\u{2764}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (3, 4, '\u{1F602}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (4, 5, '\u{1F64F}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (5, 6, '\u{2705}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (6, 2, '\u{1F44D}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (7, 3, '\u{2764}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (8, 4, '\u{1F602}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (9, 5, '\u{1F64F}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (10, 6, '\u{2705}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (11, 2, '\u{1F44D}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (12, 3, '\u{2764}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (13, 4, '\u{1F602}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (14, 5, '\u{1F64F}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (15, 6, '\u{2705}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (16, 2, '\u{1F44D}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (17, 3, '\u{2764}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (18, 4, '\u{1F602}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (19, 5, '\u{1F64F}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (20, 6, '\u{2705}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (21, 2, '\u{1F44D}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (22, 3, '\u{2764}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (23, 4, '\u{1F602}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (24, 5, '\u{1F64F}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (25, 6, '\u{2705}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (26, 2, '\u{1F44D}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (27, 3, '\u{2764}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (28, 4, '\u{1F602}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (29, 5, '\u{1F64F}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (30, 6, '\u{2705}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (31, 2, '\u{1F44D}', 1);
INSERT INTO message_reactions (`message_id`, `user_id`, `emoji_code`, `created_by`) VALUES (32, 3, '\u{2764}', 1);
