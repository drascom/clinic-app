<?php
/**
 * Patient Helper Functions
 * 
 * This file contains utility functions for working with patient-related pages
 */

/**
 * Generate a link to patient details page with optional tab
 * 
 * @param int $patient_id The patient ID
 * @param string $tab Optional tab to open (appointments, surgeries, images)
 * @param string $text Link text (optional, defaults to "View Details")
 * @param string $class CSS classes for the link (optional)
 * @param array $attributes Additional HTML attributes (optional)
 * @return string HTML link element
 */
function patient_details_link($patient_id, $tab = null, $text = 'View Details', $class = '', $attributes = []) {
    $url = "patient/patient_details.php?id=" . urlencode($patient_id);
    
    if ($tab) {
        $url .= "&tab=" . urlencode($tab);
    }
    
    $attr_string = '';
    if ($class) {
        $attr_string .= ' class="' . htmlspecialchars($class) . '"';
    }
    
    foreach ($attributes as $key => $value) {
        $attr_string .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    return '<a href="' . htmlspecialchars($url) . '"' . $attr_string . '>' . htmlspecialchars($text) . '</a>';
}

/**
 * Generate URL to patient details page with optional tab
 * 
 * @param int $patient_id The patient ID
 * @param string $tab Optional tab to open (appointments, surgeries, images)
 * @param bool $absolute Whether to return absolute URL (default: false)
 * @return string URL to patient details page
 */
function patient_details_url($patient_id, $tab = null, $absolute = false) {
    $base = $absolute ? 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] : '';
    $url = $base . "/patient/patient_details.php?id=" . urlencode($patient_id);
    
    if ($tab) {
        $url .= "&tab=" . urlencode($tab);
    }
    
    return $url;
}

/**
 * Generate JavaScript code to open patient details in new tab/window
 * 
 * @param int $patient_id The patient ID
 * @param string $tab Optional tab to open (appointments, surgeries, images)
 * @param bool $new_window Whether to open in new window (default: true)
 * @return string JavaScript code
 */
function patient_details_js($patient_id, $tab = null, $new_window = true) {
    $url = patient_details_url($patient_id, $tab);
    $target = $new_window ? '_blank' : '_self';
    
    return "window.open('" . addslashes($url) . "', '" . $target . "');";
}

/**
 * Available tabs for patient details page
 * 
 * @return array List of available tab names
 */
function get_patient_detail_tabs() {
    return [
        'appointments' => 'Appointments',
        'surgeries' => 'Surgeries', 
        'images' => 'Images'
    ];
}

/**
 * Validate if a tab name is valid
 * 
 * @param string $tab Tab name to validate
 * @return bool True if valid, false otherwise
 */
function is_valid_patient_tab($tab) {
    return array_key_exists($tab, get_patient_detail_tabs());
}

/**
 * Generate breadcrumb navigation for patient details
 * 
 * @param int $patient_id The patient ID
 * @param string $patient_name The patient name
 * @param string $current_tab Current active tab (optional)
 * @return string HTML breadcrumb navigation
 */
function patient_details_breadcrumb($patient_id, $patient_name, $current_tab = null) {
    $breadcrumb = '<nav aria-label="breadcrumb">';
    $breadcrumb .= '<ol class="breadcrumb">';
    $breadcrumb .= '<li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>';
    $breadcrumb .= '<li class="breadcrumb-item"><a href="/patient/patients.php">Patients</a></li>';
    $breadcrumb .= '<li class="breadcrumb-item"><a href="' . patient_details_url($patient_id) . '">' . htmlspecialchars($patient_name) . '</a></li>';
    
    if ($current_tab && is_valid_patient_tab($current_tab)) {
        $tabs = get_patient_detail_tabs();
        $breadcrumb .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($tabs[$current_tab]) . '</li>';
    }
    
    $breadcrumb .= '</ol>';
    $breadcrumb .= '</nav>';
    
    return $breadcrumb;
}
?>
