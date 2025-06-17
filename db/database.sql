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
  is_active INTEGER DEFAULT 0
);

CREATE TABLE surgeries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT NOT NULL,
  notes TEXT,
  status TEXT DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'Confirmed', 'Completed', 'Canceled')),
  predicted_grafts_count INTEGER,
  current_grafts_count INTEGER,
  room_id INTEGER,
  patient_id INTEGER NOT NULL,
  is_recorded BOOLEAN DEFAULT FALSE,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO surgeries (id, date, notes, status, predicted_grafts_count, current_grafts_count, room_id, patient_id, is_recorded, updated_by, created_at, updated_at) VALUES (1, '2025-06-15', 'test', 'scheduled', NULL, NULL, 1, 1, 1, 1, '2025-06-15 09:38:22', '2025-06-15 09:38:22');

CREATE TABLE appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER,
    patient_id INTEGER NOT NULL ,
    appointment_date DATE NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    procedure_id INTEGER,
    notes TEXT,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (procedure_id) REFERENCES procedures(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE patients (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT,
  dob TEXT,
  phone TEXT,
  city TEXT,
  gender TEXT DEFAULT 'N/A' CHECK (gender IN ('N/A', 'Male', 'Female', 'Transgender')),
  avatar TEXT,
  occupation TEXT,
  agency_id INTEGER NOT NULL,
  photo_album_id INTEGER,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO patients (id, name, email, dob, phone, city, gender, avatar, occupation, agency_id, photo_album_id, updated_by, created_at, updated_at) VALUES (1, 'emin colak', 'drascom07@gmail.com', '2025-06-12', '432424', 'Barnet', 'Male', NULL, '', 1, NULL, NULL, '2025-06-15 09:09:48', '2025-06-15 09:09:48');


CREATE TABLE photo_album_types (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE patient_photos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  patient_id INTEGER NOT NULL,
  photo_album_type_id INTEGER,
  file_path TEXT,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE agencies (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  updated_by INTEGER NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
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
    FOREIGN KEY (agency_id) REFERENCES agencies(id),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
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
    types TEXT,
    is_active INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE room_reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL,
    surgery_id INTEGER NOT NULL,
    reserved_date DATE NOT NULL,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(room_id, reserved_date)
);

CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT NOT NULL,
    location TEXT NOT NULL,
    position_applied TEXT NULL,
    staff_type TEXT DEFAULT 'candidate' CHECK (staff_type IN ('candidate', 'staff')),
    is_active INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS staff_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    speciality TEXT,
    experience_level TEXT CHECK (experience_level IN ('entry level', 'junior', 'mid Level', 'senior', 'expert')),
    current_company TEXT,
    linkedin_profile TEXT,
    source TEXT CHECK (source IN ('Website', 'LinkedIn', 'Indeed', 'Referral', 'Agency', 'Other')),
    salary_expectation TEXT NOT NULL,
    willing_to_relocate INTEGER DEFAULT 0,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS staff_availability (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    staff_id INTEGER NOT NULL,
    available_on DATE NOT NULL,
    period TEXT DEFAULT 'full' CHECK (period IN ('am','pm','full')),
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (staff_id, available_on, period)
);



-- New table for surgery-technician assignments
CREATE TABLE surgery_staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    surgery_id INTEGER NOT NULL,
    staff_id INTEGER NOT NULL,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (surgery_id) REFERENCES surgeries(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (surgery_id, staff_id)
);

CREATE TABLE IF NOT EXISTS procedures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    is_active INTEGER DEFAULT 1,
    updated_by INTEGER NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE settings (
  key TEXT PRIMARY KEY UNIQUE,
  value TEXT
);

INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('Pre-Surgery', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('Post-Surgery', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('Follow-up', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('1. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('2. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('3. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('4. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('5. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('6. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('7. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('8. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('9. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('10. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('11. Month', 1, datetime('now'), datetime('now'));
INSERT INTO photo_album_types (name, updated_by, created_at, updated_at) VALUES ('12. Month', 1, datetime('now'), datetime('now'));




INSERT INTO users (email, username, password,role, created_at, updated_at, agency_id, is_active) VALUES ('admin@example.com', 'Admin', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','admin', datetime('now'), datetime('now'),1,1);
INSERT INTO users (email, username, password,role, created_at, updated_at, agency_id, is_active) VALUES ('editor@example.com', 'Editor', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','editor', datetime('now'), datetime('now'),1,1);
INSERT INTO users (email, username, password,role, created_at, updated_at, agency_id,is_active) VALUES ('agent@example.com', 'Agent', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','agent', datetime('now'), datetime('now'),2,1);
INSERT INTO users (email, username, password,role, created_at, updated_at, agency_id,is_active) VALUES ('tech@example.com', 'Technician', '$2y$10$aEtcftk7GMX3bP3DqIRxQ.DmbuVMC.b18q96ziMwSQWyQO/TWuG5a','technician', datetime('now'), datetime('now'),3,1);




INSERT INTO settings (key, value) VALUES ('spreadsheet_id', '1mP20et8Pe_RMQvEC-ra2mXi9aoAtTwK_jsMwcUn9tt0');
INSERT INTO settings (key, value) VALUES ('cache_duration', '300');
INSERT INTO settings (key, value) VALUES ('cell_range', 'A1:I30');




-- Insert sample agencies for testing
INSERT INTO agencies (name, updated_by, created_at, updated_at) VALUES ('Hospital', 1, datetime('now'), datetime('now'));
INSERT INTO agencies (name, updated_by, created_at, updated_at) VALUES ('Want Hair', 1, datetime('now'), datetime('now'));
INSERT INTO agencies (name, updated_by, created_at, updated_at) VALUES ('Other Agency', 1, datetime('now'), datetime('now'));



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
CREATE INDEX IF NOT EXISTS idx_staff_availability_period ON staff_availability(period);
CREATE INDEX IF NOT EXISTS idx_staff_active ON staff(is_active);

-- Indexes for interview_invitations table
CREATE INDEX IF NOT EXISTS idx_interview_invitations_staff_id ON interview_invitations(staff_id);
CREATE INDEX IF NOT EXISTS idx_interview_invitations_date ON interview_invitations(interview_date);
CREATE INDEX IF NOT EXISTS idx_interview_invitations_sent_by ON interview_invitations(sent_by);
CREATE INDEX IF NOT EXISTS idx_interview_invitations_sent_at ON interview_invitations(sent_at);



-- Insert sample rooms for testing
INSERT INTO rooms (name, types, is_active, updated_by) VALUES
('Surgery 1',  'surgery', 1, 1),
('Surgery 2',  'surgery', 0, 1),
('Surgery 3',  'surgery', 0, 1),
('Consultation',  'consultation', 1, 1),
('Cosmetology',  'treatment', 1, 1);

-- Insert demo staff 

-- Insert default procedures
INSERT INTO procedures (name, is_active, updated_by) VALUES
('Consultation', 1, 1),
('Botox', 1, 1),
('Dermal Fillers', 1, 1),
('PRP (Platelet-Rich Plasma)', 1, 1),
('Microneedling', 1, 1),
('HydraFacial', 1, 1),
('Chemical Peel', 1, 1),
('Laser Hair Removal', 1, 1),
('Skin Rejuvenation', 1, 1),
('Carbon Laser Peel', 1, 1),
('Mesotherapy', 1, 1),
('Facial Cleansing', 1, 1),
('Radiofrequency Skin Tightening', 1, 1),
('Cryolipolysis (Fat Freezing)', 1, 1),
('Ultherapy', 1, 1),
('LED Light Therapy', 1, 1),
('OxyGeneo Facial', 1, 1),
('Hollywood Peel', 1, 1),
('Aqualyx Fat Dissolving', 1, 1),
('Carboxytherapy', 1, 1),
('Lip Augmentation', 1, 1),
('Jawline Contouring', 1, 1);



-- Insert sample staff from job_candidates-export.json
INSERT INTO staff (name, email, phone, position_applied, location, staff_type,updated_by) VALUES ('esra bilen', 'esrabilen07@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type,updated_by) VALUES ('ferhat - emine demirtas', 'tahirferhatdemirtas@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('goktug gokalp', 'goktuggokalp34@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('irem cengiz', 'iremcngc@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('muhammed arici', 'muhammetposof28@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('ali emre dogan', 'aedogan.saglik@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('naz ilhan', 'nazilhan1040@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('nesibe idil karpuz', 'n.idilkrp@icloud.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('onur meric bugan', 'onurmeric03@icloud.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('sametcan balci', 'samet42550.97@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('zelal celikten', 'zelalcelikten@gmail.com', 'update', 'Hair Transplant Technician', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('jhon', 'test@rser.fds', '324242', 'csdsad', 'turkiye', 'candidate', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Shefiu', 'update', '07508400686', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Phandira', 'update', '07424722738', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Eniye', 'update', '07405497373', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Nava', 'update', '07435525455', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Chandu', 'update', '07435525358', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Maryam', 'update', '07775541099', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Milena', 'update', '07857273383', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Mahsa – Zahra', 'update', '07914262872', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Beverly', 'update', '07775434126', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Claduio', 'update', '00393341817614', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Sahnaz', 'update', '07404324662', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Sravani', 'update', '07508858512', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Sagun Khadka', 'update', '07380576839', 'senior', 'UK', 'staff', 1);
INSERT INTO staff (name, email, phone, position_applied, location, staff_type, updated_by) VALUES ('Monisha', 'update', '07436422647', 'senior', 'UK', 'staff', 1);
