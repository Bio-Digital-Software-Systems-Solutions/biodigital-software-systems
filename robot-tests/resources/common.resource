*** Settings ***
Documentation     Common resources and keywords for ICC Munich tests
Library           Browser
Library           RequestsLibrary
Library           Collections
Library           String
Library           DateTime
Library           JSONLibrary
Library           DatabaseLibrary

*** Variables ***
${BASE_URL}                 http://nginx
${API_URL}                  http://nginx/api
${BROWSER}                  chromium
${HEADLESS}                 true
${DEFAULT_TIMEOUT}          30s
${DB_HOST}                  mysql
${DB_PORT}                  3306
${DB_NAME}                  icc_munich
${DB_USER}                  icc_user
${DB_PASSWORD}              secret

# Test user credentials
${TEST_USER_EMAIL}          test@example.com
${TEST_USER_PASSWORD}       SecurePassword123!
${ADMIN_EMAIL}              admin@example.com
${ADMIN_PASSWORD}           password

*** Keywords ***
Open Browser To Application
    [Documentation]    Opens a new browser instance and navigates to the application
    [Arguments]    ${url}=${BASE_URL}
    New Browser    ${BROWSER}    headless=${HEADLESS}
    New Context    viewport={'width': 1920, 'height': 1080}
    New Page    ${url}
    Wait For Load State    networkidle

Close Browser Session
    [Documentation]    Closes the current browser session
    Close Browser

Login As User
    [Documentation]    Logs in as a regular user
    [Arguments]    ${email}=${TEST_USER_EMAIL}    ${password}=${TEST_USER_PASSWORD}
    Open Browser To Application    ${BASE_URL}/login
    Fill Text    input[name="email"]    ${email}
    Fill Text    input[name="password"]    ${password}
    Click    button[type="submit"]
    Wait For Load State    networkidle
    Wait For Elements State    text=Dashboard    visible    timeout=${DEFAULT_TIMEOUT}

Login As Admin
    [Documentation]    Logs in as an admin user
    Login As User    ${ADMIN_EMAIL}    ${ADMIN_PASSWORD}

Logout
    [Documentation]    Logs out the current user
    Click    [data-testid="user-menu"]
    Click    text=Log Out
    Wait For Load State    networkidle

Take Screenshot On Failure
    [Documentation]    Takes a screenshot when a test fails
    ${timestamp}=    Get Current Date    result_format=%Y%m%d_%H%M%S
    Take Screenshot    failure_${timestamp}

# API Keywords
Create API Session
    [Documentation]    Creates a session for API testing
    [Arguments]    ${alias}=api
    Create Session    ${alias}    ${API_URL}    verify=${False}

Get CSRF Token
    [Documentation]    Gets the CSRF token from the application
    ${response}=    GET On Session    api    /sanctum/csrf-cookie
    ${cookies}=    Get Cookies
    RETURN    ${cookies}

API Login
    [Documentation]    Logs in via API and returns the auth token
    [Arguments]    ${email}    ${password}
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary    email=${email}    password=${password}
    ${response}=    POST On Session    api    /login    json=${data}    headers=${headers}
    Should Be Equal As Strings    ${response.status_code}    200
    RETURN    ${response}

# Database Keywords
Connect To Test Database
    [Documentation]    Connects to the test database
    Connect To Database    pymysql    ${DB_NAME}    ${DB_USER}    ${DB_PASSWORD}    ${DB_HOST}    ${DB_PORT}

Disconnect From Test Database
    [Documentation]    Disconnects from the test database
    Disconnect From Database

Execute SQL Query
    [Documentation]    Executes a SQL query and returns the results
    [Arguments]    ${query}
    ${result}=    Query    ${query}
    RETURN    ${result}

Verify Record Exists In Database
    [Documentation]    Verifies that a record exists in the database
    [Arguments]    ${table}    ${column}    ${value}
    ${query}=    Set Variable    SELECT COUNT(*) FROM ${table} WHERE ${column} = '${value}'
    ${count}=    Query    ${query}
    Should Be True    ${count}[0][0] > 0    Record not found in ${table} where ${column}='${value}'

Clean Test Data
    [Documentation]    Cleans up test data from the database
    [Arguments]    ${table}    ${column}    ${value}
    Execute Sql String    DELETE FROM ${table} WHERE ${column} = '${value}'

# Utility Keywords
Generate Random Email
    [Documentation]    Generates a random email address
    ${random}=    String.Generate Random String    8    [LOWER]
    ${email}=    Set Variable    test_${random}@example.com
    RETURN    ${email}

Generate Test Random String
    [Documentation]    Generates a random string
    [Arguments]    ${length}=8    ${chars}=[LETTERS]
    ${string}=    String.Generate Random String    ${length}    ${chars}
    RETURN    ${string}

Wait For Element And Click
    [Documentation]    Waits for an element to be visible and clicks it
    [Arguments]    ${selector}
    Wait For Elements State    ${selector}    visible    timeout=${DEFAULT_TIMEOUT}
    Click    ${selector}

Wait For Element And Fill
    [Documentation]    Waits for an element to be visible and fills it
    [Arguments]    ${selector}    ${text}
    Wait For Elements State    ${selector}    visible    timeout=${DEFAULT_TIMEOUT}
    Fill Text    ${selector}    ${text}

Assert Page Contains Text
    [Documentation]    Asserts that the page contains specific text
    [Arguments]    ${text}
    Wait For Elements State    text=${text}    visible    timeout=${DEFAULT_TIMEOUT}

Assert Page Does Not Contain Text
    [Documentation]    Asserts that the page does not contain specific text
    [Arguments]    ${text}
    Wait For Elements State    text=${text}    hidden    timeout=${DEFAULT_TIMEOUT}

Assert Element Is Visible
    [Documentation]    Asserts that an element is visible
    [Arguments]    ${selector}
    Wait For Elements State    ${selector}    visible    timeout=${DEFAULT_TIMEOUT}

Assert Element Is Not Visible
    [Documentation]    Asserts that an element is not visible
    [Arguments]    ${selector}
    Wait For Elements State    ${selector}    hidden    timeout=${DEFAULT_TIMEOUT}
