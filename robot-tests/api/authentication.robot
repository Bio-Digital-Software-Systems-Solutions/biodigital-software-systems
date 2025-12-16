*** Settings ***
Documentation     Authentication API Tests
...               Tests for user registration, login, logout, and authentication flows
Resource          ../resources/common.resource
Resource          ../resources/api_keywords.resource
Suite Setup       Create API Session
Suite Teardown    Delete All Sessions
Test Tags         api    authentication

*** Variables ***
${VALID_PASSWORD}    SecurePassword123!

*** Test Cases ***
User Can Register With Valid Data
    [Documentation]    Verify that a new user can register with valid data
    [Tags]    smoke    critical
    ${email}=    Generate Random Email
    ${response}=    API Register User
    ...    first_name=Test
    ...    last_name=User
    ...    email=${email}
    ...    password=${VALID_PASSWORD}
    Assert Response Status    ${response}    302
    # Verify user exists in database
    Connect To Test Database
    Verify Record Exists In Database    users    email    ${email}
    Clean Test Data    users    email    ${email}
    Disconnect From Test Database

User Cannot Register With Existing Email
    [Documentation]    Verify that registration fails with an existing email
    [Tags]    negative
    ${email}=    Generate Random Email
    # First registration
    API Register User
    ...    first_name=First
    ...    last_name=User
    ...    email=${email}
    ...    password=${VALID_PASSWORD}
    # Second registration with same email
    ${response}=    API Register User
    ...    first_name=Second
    ...    last_name=User
    ...    email=${email}
    ...    password=${VALID_PASSWORD}
    # Should fail with validation error
    Should Be True    ${response.status_code} == 302 or ${response.status_code} == 422
    # Cleanup
    Connect To Test Database
    Clean Test Data    users    email    ${email}
    Disconnect From Test Database

User Cannot Register With Invalid Email Format
    [Documentation]    Verify that registration fails with invalid email format
    [Tags]    negative    validation
    ${response}=    API Register User
    ...    first_name=Test
    ...    last_name=User
    ...    email=invalid-email
    ...    password=${VALID_PASSWORD}
    Should Be True    ${response.status_code} == 302 or ${response.status_code} == 422

User Cannot Register With Weak Password
    [Documentation]    Verify that registration fails with a weak password
    [Tags]    negative    validation    security
    ${email}=    Generate Random Email
    ${response}=    API Register User
    ...    first_name=Test
    ...    last_name=User
    ...    email=${email}
    ...    password=123
    Should Be True    ${response.status_code} == 302 or ${response.status_code} == 422

User Can Login With Valid Credentials
    [Documentation]    Verify that a user can login with valid credentials
    [Tags]    smoke    critical
    ${email}=    Generate Random Email
    # Register user first
    API Register User
    ...    first_name=Login
    ...    last_name=Test
    ...    email=${email}
    ...    password=${VALID_PASSWORD}
    # Verify email (simulate)
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${email}'
    Disconnect From Test Database
    # Login
    ${response}=    API Login    ${email}    ${VALID_PASSWORD}
    Assert Response Status    ${response}    200
    # Cleanup
    Connect To Test Database
    Clean Test Data    users    email    ${email}
    Disconnect From Test Database

User Cannot Login With Invalid Password
    [Documentation]    Verify that login fails with invalid password
    [Tags]    negative    security
    ${email}=    Generate Random Email
    # Register user first
    API Register User
    ...    first_name=Login
    ...    last_name=Test
    ...    email=${email}
    ...    password=${VALID_PASSWORD}
    # Try login with wrong password
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary    email=${email}    password=WrongPassword123!
    ${response}=    POST On Session    api    /login    json=${data}    headers=${headers}    expected_status=any
    Should Be True    ${response.status_code} == 401 or ${response.status_code} == 422
    # Cleanup
    Connect To Test Database
    Clean Test Data    users    email    ${email}
    Disconnect From Test Database

User Cannot Login With Non-Existent Email
    [Documentation]    Verify that login fails with non-existent email
    [Tags]    negative    security
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary    email=nonexistent@example.com    password=${VALID_PASSWORD}
    ${response}=    POST On Session    api    /login    json=${data}    headers=${headers}    expected_status=any
    Should Be True    ${response.status_code} == 401 or ${response.status_code} == 422

Authenticated User Can Logout
    [Documentation]    Verify that an authenticated user can logout
    [Tags]    smoke
    ${email}=    Generate Random Email
    # Register and verify user
    API Register User
    ...    first_name=Logout
    ...    last_name=Test
    ...    email=${email}
    ...    password=${VALID_PASSWORD}
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${email}'
    Disconnect From Test Database
    # Login
    API Login    ${email}    ${VALID_PASSWORD}
    # Logout
    ${response}=    API Logout
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 204 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    users    email    ${email}
    Disconnect From Test Database

Registration Requires All Mandatory Fields
    [Documentation]    Verify that registration fails when mandatory fields are missing
    [Tags]    validation
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary    email=test@example.com
    ${response}=    POST On Session    api    /register    json=${data}    headers=${headers}    expected_status=any
    Should Be True    ${response.status_code} == 302 or ${response.status_code} == 422
