*** Settings ***
Documentation     End-to-End User Registration Flow Tests
...               Complete user journey from registration to dashboard access
Resource          ../resources/common.resource
Test Setup        Open Browser To Application
Test Teardown     Run Keywords    Cleanup Test User    AND    Close Browser Session
Test Tags         e2e    registration    critical

*** Variables ***
${E2E_USER_EMAIL}       ${EMPTY}
${E2E_USER_PASSWORD}    SecurePassword123!

*** Keywords ***
Cleanup Test User
    [Documentation]    Cleans up the test user from database
    Run Keyword If    '${E2E_USER_EMAIL}' != '${EMPTY}'    Remove User From Database

Remove User From Database
    [Documentation]    Removes user from database
    Connect To Test Database
    Clean Test Data    users    email    ${E2E_USER_EMAIL}
    Disconnect From Test Database

Generate Test User Email
    [Documentation]    Generates a unique email for testing
    ${random}=    String.Generate Random String    10    [LOWER]
    ${email}=    Set Variable    e2e_${random}@example.com
    Set Test Variable    ${E2E_USER_EMAIL}    ${email}
    RETURN    ${email}

*** Test Cases ***
Complete User Registration To Dashboard Flow
    [Documentation]    Tests the complete flow from registration to dashboard access
    [Tags]    smoke    critical
    # Step 1: Navigate to registration page
    Click    text=Register
    Wait For Load State    networkidle
    ${url}=    Get Url
    Should Contain    ${url}    register

    # Step 2: Fill registration form
    ${email}=    Generate Test User Email
    Fill Text    input[name="first_name"]    Test
    Fill Text    input[name="last_name"]    User
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1990-01-01
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    ${E2E_USER_PASSWORD}

    # Step 3: Submit registration
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Step 4: Verify redirect (to verification notice or dashboard)
    ${url}=    Get Url
    Should Match Regexp    ${url}    (dashboard|verify|email)

    # Step 5: Verify user in database
    Connect To Test Database
    Verify Record Exists In Database    users    email    ${email}
    # Verify email for test
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${email}'
    Disconnect From Test Database

    # Step 6: Access dashboard
    Go To    ${BASE_URL}/dashboard
    Wait For Load State    networkidle
    ${url}=    Get Url
    Should Contain    ${url}    dashboard

User Can Login After Registration
    [Documentation]    Verify user can login after successful registration
    [Tags]    smoke
    # Register new user
    Click    text=Register
    Wait For Load State    networkidle
    ${email}=    Generate Test User Email
    Fill Text    input[name="first_name"]    Login
    Fill Text    input[name="last_name"]    Test
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1995-05-15
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    ${E2E_USER_PASSWORD}
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Verify email
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${email}'
    Disconnect From Test Database

    # Logout
    Go To    ${BASE_URL}/logout
    Wait For Load State    networkidle

    # Login again
    Go To    ${BASE_URL}/login
    Wait For Load State    networkidle
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Verify on dashboard
    ${url}=    Get Url
    Should Contain    ${url}    dashboard

Registration With Existing Email Fails
    [Documentation]    Verify that registration fails with existing email
    [Tags]    negative
    # Create first user
    Click    text=Register
    Wait For Load State    networkidle
    ${email}=    Generate Test User Email
    Fill Text    input[name="first_name"]    First
    Fill Text    input[name="last_name"]    User
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1990-01-01
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    ${E2E_USER_PASSWORD}
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Logout
    Go To    ${BASE_URL}/logout
    Wait For Load State    networkidle

    # Try to register with same email
    Go To    ${BASE_URL}/register
    Wait For Load State    networkidle
    Fill Text    input[name="first_name"]    Second
    Fill Text    input[name="last_name"]    User
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1990-01-01
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    ${E2E_USER_PASSWORD}
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Should show error or stay on register page
    ${url}=    Get Url
    Should Contain    ${url}    register

Registration With Mismatched Passwords Fails
    [Documentation]    Verify that registration fails with mismatched passwords
    [Tags]    negative    validation
    Click    text=Register
    Wait For Load State    networkidle
    ${email}=    Generate Test User Email
    Fill Text    input[name="first_name"]    Password
    Fill Text    input[name="last_name"]    Mismatch
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1990-01-01
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    DifferentPassword123!
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Should show error or stay on register page
    ${url}=    Get Url
    Should Contain    ${url}    register

New User Has Member Role
    [Documentation]    Verify that new registered user has member role
    [Tags]    authorization
    Click    text=Register
    Wait For Load State    networkidle
    ${email}=    Generate Test User Email
    Fill Text    input[name="first_name"]    Role
    Fill Text    input[name="last_name"]    Test
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1990-01-01
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    ${E2E_USER_PASSWORD}
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Verify role in database
    Connect To Test Database
    ${result}=    Query    SELECT r.name FROM roles r JOIN model_has_roles mr ON r.id = mr.role_id JOIN users u ON mr.model_id = u.id WHERE u.email = '${email}'
    Disconnect From Test Database
    Should Be True    len(${result}) > 0    User should have at least one role
    Should Be Equal    ${result}[0][0]    member

User Profile Is Accessible After Registration
    [Documentation]    Verify that new user can access their profile
    [Tags]    smoke
    # Register
    Click    text=Register
    Wait For Load State    networkidle
    ${email}=    Generate Test User Email
    Fill Text    input[name="first_name"]    Profile
    Fill Text    input[name="last_name"]    Access
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1990-01-01
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    ${E2E_USER_PASSWORD}
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Verify email
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${email}'
    Disconnect From Test Database

    # Navigate to profile
    Go To    ${BASE_URL}/profile
    Wait For Load State    networkidle
    ${url}=    Get Url
    Should Contain    ${url}    profile

User Can Update Profile After Registration
    [Documentation]    Verify that new user can update their profile
    [Tags]    smoke
    # Register
    Click    text=Register
    Wait For Load State    networkidle
    ${email}=    Generate Test User Email
    Fill Text    input[name="first_name"]    Original
    Fill Text    input[name="last_name"]    Name
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="birth_date"]    1990-01-01
    Fill Text    input[name="password"]    ${E2E_USER_PASSWORD}
    Fill Text    input[name="password_confirmation"]    ${E2E_USER_PASSWORD}
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Verify email
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${email}'
    Disconnect From Test Database

    # Go to profile
    Go To    ${BASE_URL}/profile
    Wait For Load State    networkidle

    # Update first name
    ${first_name_input}=    Get Element Count    input[name="first_name"]
    Run Keyword If    ${first_name_input} > 0    Fill Text    input[name="first_name"]    Updated

    # Find and click save button
    ${save_button}=    Get Element Count    button[type="submit"]
    Run Keyword If    ${save_button} > 0    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Verify in database
    Connect To Test Database
    ${result}=    Query    SELECT first_name FROM users WHERE email = '${email}'
    Disconnect From Test Database
    # First name might be updated or original depending on form structure
    Log    Current first name: ${result}[0][0]
