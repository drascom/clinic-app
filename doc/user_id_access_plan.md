# Plan: Secure and Consistent User ID Access

This document outlines the plan to develop a robust and secure system for consistently accessing the logged-in user's ID across all application pages, excluding authentication-specific pages.

## Goal 1: Ensure Consistent Backend User ID Access

**Description:** Modify all relevant backend PHP pages (excluding authentication pages) to include `public/auth/auth.php` and use the `get_user_id()` function to retrieve the logged-in user's ID. This ensures a standardized and secure way to get the user ID on the server-side.

**Steps:**

1.  **Identify Backend Pages:** Identify all PHP pages that are not part of the authentication flow (e.g., `public/dashboard.php`, pages in `public/patient/`, `public/staff/`, `public/admin/`, etc.).
2.  **Add `require_once`:** For each identified page, add `require_once __DIR__ . '/../auth/auth.php';` (or the appropriate relative path) at the top of the file.
3.  **Replace Direct Session Access:** Replace any direct `$_SESSION['user_id']` access with `get_user_id()`.
4.  **Implement Redirection for Unauthenticated Users:** For pages that require authentication, add a check at the top to redirect unauthenticated users to the login page.

## Goal 2: Implement Secure Frontend User ID Access

**Description:** Create a secure API endpoint to provide the logged-in user's ID to the frontend, and modify `api-helper.js` to include this ID in all API requests. This avoids embedding the ID directly in HTML and ensures all API calls are associated with the authenticated user.

**Steps:**

1.  **Create a new API handler for user session data:**
    - Create a new file: `public/api_handlers/session.php`.
    - Implement a `handle_session()` function that returns the `user_id`, `username`, `user_role`, and `agency_id` from the `$_SESSION` superglobal. This endpoint will be accessible only to authenticated users.
2.  **Modify `public/api.php` to include `auth.php`:** Ensure `public/api.php` includes `public/auth/auth.php` at the very beginning to make session variables available.
3.  **Update `public/assets/js/api-helper.js`:**
    - Add a function to fetch the user session data from the new `session.php` endpoint.
    - Modify the `apiRequest()` function to include the `user_id` in the `data` payload of every request.

## Goal 3: Update `created_by` and `updated_by` in API Handlers

**Description:** Leverage the `authenticated_user_id` passed from `public/api.php` to automatically populate `created_by` and `updated_by` fields in relevant API handlers, rather than relying on client-side input for these values.

**Steps:**

1.  **Identify Relevant API Handlers:** Review API handlers like `public/api_handlers/users.php`, `public/api_handlers/patients.php`, `public/api_handlers/appointments.php`, etc., to find where `created_by` and `updated_by` are used.
2.  **Replace Input with Authenticated User ID:** In these handlers, replace `$input['created_by'] ?? null;` with `$input['authenticated_user_id'] ?? null;` and similarly for `updated_by`.

## Goal 4: Review and Enhance Security (Optional but Recommended)

**Description:** Briefly review existing security measures and suggest enhancements if critical vulnerabilities are identified.

**Steps:**

1.  **Review Session Management:** Confirm `session_regenerate_id(true)` is used on login.
2.  **Consider CSRF:** If sensitive forms are identified, suggest implementing CSRF tokens.

## System Flow Diagram

```mermaid
graph TD
    subgraph Frontend
        A[User Browser] --> B(Load Page)
        B --> C{Page Requires User ID?}
        C -- Yes --> D(JavaScript `getUserId()` function)
        D --> E(Call API: `session.php?action=get_user_session`)
        E --> F(Receive User ID from API)
        F --> G(Use User ID in UI/API Requests)
        G --> H(API Request with User ID)
    end

    subgraph Backend
        I[PHP Page Request] --> J(Include `auth.php`)
        J --> K(Access `get_user_id()`)
        K --> L(Use User ID for Logic/DB Operations)

        M[API Request to `api.php`] --> N(Include `auth.php`)
        N --> O(Extract `$_SESSION['user_id']`)
        O --> P(Pass `authenticated_user_id` to Handler)
        P --> Q(API Handler uses `authenticated_user_id`)
    end

    E -- HTTP Request --> M
    Q -- DB Operations --> R[Database]
    L -- DB Operations --> R
```
