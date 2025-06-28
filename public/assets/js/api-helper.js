/**
 * Global API Helper for secure POST-based API requests
 * This ensures all API calls use POST method to hide entity/action parameters from URLs
 */

// Global variable to store user session data
let userSession = null;

/**
 * Fetches the user session from the server.
 * @returns {Promise<Object>} - A promise that resolves to the user session object.
 */
async function getUserSession() {
  if (userSession) {
    return userSession;
  }

  try {
    const response = await fetch("/api.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        entity: "session",
        action: "get_user_session",
      }),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    if (result.success) {
      userSession = result.user;
      return userSession;
    } else {
      // Handle cases where the user is not logged in
      console.warn("User session not found or user not logged in.");
      return null;
    }
  } catch (error) {
    console.error("Failed to get user session:", error);
    throw error;
  }
}

/**
 * Make a secure API request using POST method.
 * It now automatically includes the logged-in user's ID.
 * @param {string} entity - The API entity (e.g., 'patients', 'rooms')
 * @param {string} action - The API action (e.g., 'list', 'get', 'add')
 * @param {Object} data - Additional data to send (optional)
 * @returns {Promise} - Promise that resolves to the API response
 */
async function apiRequest(entity, action, data = {}) {
  // Ensure user session is fetched before making other API calls
  const session = await getUserSession();

  // Add authenticated user ID to every request
  if (session && session.id) {
    data.authenticated_user_id = session.id;
  }

  const requestBody = {
    entity,
    action,
    ...data,
  };

  try {
    const response = await fetch("/api.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(requestBody),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.error("API Request Error:", error);
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

  const entity = params.get("entity");
  const action = params.get("action");

  if (!entity || !action) {
    throw new Error("Invalid API URL: missing entity or action");
  }

  // Extract all other parameters
  const data = {};
  params.forEach((value, key) => {
    if (key !== "entity" && key !== "action") {
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
    json: () => secureFetch(url),
  };
}

// Export functions for use in other scripts
window.apiRequest = apiRequest;
window.secureFetch = secureFetch;
window.fetchCompat = fetchCompat;
window.getUserSession = getUserSession;
