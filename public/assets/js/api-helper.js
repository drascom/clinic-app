/**
 * Global API Helper for secure POST-based API requests
 * This ensures all API calls use POST method to hide entity/action parameters from URLs
 */

/**
 * Make a secure API request using POST method
 * @param {string} entity - The API entity (e.g., 'patients', 'rooms')
 * @param {string} action - The API action (e.g., 'list', 'get', 'add')
 * @param {Object} data - Additional data to send (optional)
 * @returns {Promise} - Promise that resolves to the API response
 */
async function apiRequest(entity, action, data = {}) {
    const formData = new FormData();
    formData.append('entity', entity);
    formData.append('action', action);

    // Add any additional data
    Object.keys(data).forEach(key => {
        if (data[key] !== null && data[key] !== undefined) {
            formData.append(key, data[key]);
        }
    });

    try {
        const response = await fetch('/api.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API Request Error:', error);
        throw error;
    }
}

/**
 * Legacy fetch wrapper for backward compatibility
 * Converts old GET-style API calls to secure POST calls
 * @param {string} url - The original URL (will be parsed for entity/action)
 * @returns {Promise} - Promise that resolves to the API response
 */
async function secureFetch(url) {
    // Parse the URL to extract entity, action, and parameters
    const urlObj = new URL(url, window.location.origin);
    const params = new URLSearchParams(urlObj.search);

    const entity = params.get('entity');
    const action = params.get('action');

    if (!entity || !action) {
        throw new Error('Invalid API URL: missing entity or action');
    }

    // Extract all other parameters
    const data = {};
    params.forEach((value, key) => {
        if (key !== 'entity' && key !== 'action') {
            data[key] = value;
        }
    });

    return apiRequest(entity, action, data);
}

/**
 * Wrapper that returns a response-like object for compatibility
 * @param {string} url - The API URL
 * @returns {Object} - Response-like object with json() method
 */
function fetchCompat(url) {
    return {
        json: () => secureFetch(url)
    };
}

// Export functions for use in other scripts
window.apiRequest = apiRequest;
window.secureFetch = secureFetch;
window.fetchCompat = fetchCompat;
