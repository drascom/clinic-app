<?php
require_once __DIR__ . '/../includes/db.php';

$db = get_db();

// The CSV data provided by the user
$csvData = "
,Hair Transplant Day,Day,Patient Name,Technician 1,Technician 2
,25th March 2025,Tuesday,Danny Alcock,Shefu,Nava
,27th March 2025,Thursday,William Hunt,Shefu,Nava
,7th April 2025,Monday,Joe Robson,Shefu,Phanindra
,8th April 2025,Tuesday,David Andrew Jones,Shefu,Phanindra
,9th April 2025,Wednesday,Luke Parker,Shefu,Phanindra
,11th April 2025,Friday,Chamal Hettiarachchi,Shefu,Chandu
,14th April 2025,Monday,Oli Mason,Shefu,Phanindra
,23th April 2025,Wednesday,B Hudges,Shefu,Phanindra
,24th April 2025,Thursday,Ben Arney,Maryam,Eniye
,25th April 2025,Friday,Damien Conybeare Jones,Shefu,Maryam
,28th April 2025,Monday,Lucifer Oshea,Shefu,Phanindra
,29th April 2025,Tuesday,Priyesh Pattni,Shefu,Milena
,2nd May 2025,Friday,William Michael Pentland,Shefu,Eniye
,12th May 2025,Monday,Steve Pardoe,Shefu,Phanindra
,13th May 2025,Tuesday,Nate Turner,Shefu,Phanindra
,14th May 2025,Wednesday,Andy Newell,Shefu,Phanindra
,15th May 2025,Thursday,Thomas Gray,Shefu,Eniye
,16th May 2025,Friday,Joe Collaway,Shefu,Eniye
,19th May 2025,Monday,Sipan Mohammed,Shefu,Phanindra
,20th May 2025,Tuesday,Bradley Wilson,Shefu,Phanindra
,21st May 2025,Wednesday,Tony Morgan,Shefu,Phanindra
,22nd May 2025,Thursday,Kris Bell,Shefu,Eniye
,23rd May 2025,Friday,Jack Hanney,Shefu,Eniye
,27th May 2025,Tuesday,Andrew Lewis,Shefu,Phanindra
,28th May 2025,Wednesday,Andrew Lewis,Shefu,Phanindra
,29th May 2025,Thursday,Bob Cesna,Shefu,Eniye
,2nd June 2025,Monday,Daniel Wilson Dunwell,Shefu,Eniye
,3rd June 2025,Tuesday,Wojciech Etz,Sahnaz ,Sravani
,4th June 2025,Wednesday,Dan Storey,Sahnaz ,Sravani
,10th June 2025,Tuesday,Jamie Wanless,Shefu,Sravani
,12th May 2025,Thursday,Leo Swinfen,John,Eniye
,13th May 2025,Friday,Danny Leach,John,Sravani
,16th June 2025,Monday,Sean Mullins,John,Shefu
,17th June 2025,Tuesday,Hassan Eshaghy,John,Sravani
,19th June 2025,Thursday,Henry Dorkin,Maryam,Eniye
,20th June 2025,Friday,Bradley Reynolds,Shefu,Sravani
,25th June 2025,Wednesday,Martin Bleakley,John,Sravani
,26th June 2025,Thursday,Nathan Sutcliffe,John,Sravani
";

$staffPhoneNumbers = [
    'Shefiu' => '07508400686',
    'Phandira' => '07424722738',
    'Eniye' => '07405497373',
    'Nava' => '07435525455',
    'Chandu' => '07435525358',
    'Maryam' => '07775541099',
    'Milena' => '07857273383',
    'Mahsa – Zahra' => '07914262872',
    'Beverly' => '07775434126',
    'Claduio' => '00393341817614',
    'Sahnaz' => '07404324662',
    'Sravani' => '07508858512',
    'Sagun Khadka' => '07380576839',
    'Monisha' => '07436422647',
    'John Immanuel' => '07554704110'
];


// Function to get patient ID by name
function getPatientId($db, $patientName)
{
    $searchTerm = '%' . str_replace(' ', '%', $patientName) . '%';
    $stmt = $db->prepare("SELECT id FROM patients WHERE name LIKE ?");
    $stmt->execute([$searchTerm]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

// Function to get staff ID by name, or create a new staff member if not found
function getOrCreateStaffId($db, $staffName, $staffPhone)
{
    $stmt = $db->prepare("SELECT id, phone FROM staff WHERE name = ?");
    $stmt->execute([$staffName]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Staff exists, check if phone number needs updating
        if ($staffPhone && $result['phone'] !== $staffPhone) {
            $updateStmt = $db->prepare("UPDATE staff SET phone = ? WHERE id = ?");
            $updateStmt->execute([$staffPhone, $result['id']]);
        }
        return $result['id'];
    } else {
        // Staff does not exist, create new
        $insertStmt = $db->prepare("INSERT INTO staff (name, email, phone, location, staff_type, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$staffName, "default@example.com", $staffPhone, "Default Location", "staff", 1, 1]);
        return $db->lastInsertId();
    }
}

// Function to get surgery ID by patient ID and date, or create a new one if not found
function getOrCreateSurgeryId($db, $patientId, $date)
{
    // First, try to find the surgery
    $stmt = $db->prepare("SELECT id FROM surgeries WHERE patient_id = ? AND date = ?");
    $stmt->execute([$patientId, $date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Surgery exists, return its ID
        return $result['id'];
    } else {
        // Surgery does not exist, create a new one
        $insertStmt = $db->prepare("INSERT INTO surgeries (patient_id, date, created_by, updated_by) VALUES (?, ?, ?, ?)");
        // Using default values for other fields. Assuming user with id 1 is the creator.
        $insertStmt->execute([$patientId, $date, 1, 1]);
        echo "Created new surgery for patient ID: $patientId on $date<br>";
        return $db->lastInsertId();
    }
}

// Parse the CSV data
$lines = explode("\n", trim($csvData));
$header = str_getcsv(array_shift($lines), ',', '"', '\\');

foreach ($lines as $line) {
    $row = str_getcsv($line, ',', '"', '\\');
    $rowData = array_combine($header, $row);

    $patientName = trim($rowData['Patient Name']);
    $surgeryDateStr = trim($rowData['Hair Transplant Day']);
    $technician1 = trim($rowData['Technician 1']);
    $technician2 = trim($rowData['Technician 2']);

    // Format the date correctly
    $date = date('Y-m-d', strtotime($surgeryDateStr));

    $patientId = getPatientId($db, $patientName);

    if ($patientId) {
        $surgeryId = getOrCreateSurgeryId($db, $patientId, $date);

        if ($surgeryId) {
            $phone1 = $staffPhoneNumbers[$technician1] ?? null;
            $phone2 = $staffPhoneNumbers[$technician2] ?? null;

            $staffId1 = getOrCreateStaffId($db, $technician1, $phone1);
            $staffId2 = getOrCreateStaffId($db, $technician2, $phone2);

            // Insert into surgery_staff
            $insertStmt = $db->prepare("INSERT OR IGNORE INTO surgery_staff (surgery_id, staff_id, created_by, updated_by) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$surgeryId, $staffId1, 1, 1]);
            $insertStmt->execute([$surgeryId, $staffId2, 1, 1]);

            echo "Processed surgery for patient: $patientName on $date<br>";
        }
    } else {
        echo "Patient not found: $patientName<br>";
    }
}

echo "Import complete.";
?>