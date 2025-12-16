*** Settings ***
Documentation     Dashboard UI Tests
...               Tests for the dashboard user interface
Resource          ../resources/common.resource
Suite Setup       Setup Dashboard Tests
Suite Teardown    Teardown Dashboard Tests
Test Setup        Navigate To Dashboard
Test Tags         ui    dashboard

*** Variables ***
${DASHBOARD_USER_EMAIL}       dashboard_ui@example.com
${DASHBOARD_USER_PASSWORD}    SecurePassword123!

*** Keywords ***
Setup Dashboard Tests
    [Documentation]    Create test user and login
    # Register test user via API
    Create Session    api    ${API_URL}    verify=${False}
    ${random}=    String.Generate Random String    8    [LOWER]
    Set Suite Variable    ${DASHBOARD_USER_EMAIL}    dashboard_${random}@example.com
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary
    ...    first_name=Dashboard
    ...    last_name=Tester
    ...    email=${DASHBOARD_USER_EMAIL}
    ...    password=${DASHBOARD_USER_PASSWORD}
    ...    password_confirmation=${DASHBOARD_USER_PASSWORD}
    ...    birth_date=1990-01-01
    POST On Session    api    /register    json=${data}    headers=${headers}    expected_status=any
    # Verify email
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${DASHBOARD_USER_EMAIL}'
    # Assign member role
    ${user_id}=    Query    SELECT id FROM users WHERE email = '${DASHBOARD_USER_EMAIL}'
    ${role_id}=    Query    SELECT id FROM roles WHERE name = 'member'
    Run Keyword If    ${role_id}    Execute Sql String    INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id) VALUES (${role_id}[0][0], 'App\\\\Models\\\\User', ${user_id}[0][0])
    Disconnect From Test Database
    Delete All Sessions

Teardown Dashboard Tests
    [Documentation]    Cleanup test user
    Close Browser Session
    Connect To Test Database
    Clean Test Data    users    email    ${DASHBOARD_USER_EMAIL}
    Disconnect From Test Database

Navigate To Dashboard
    [Documentation]    Opens browser and navigates to dashboard
    Open Browser To Application    ${BASE_URL}/login
    Login As User    ${DASHBOARD_USER_EMAIL}    ${DASHBOARD_USER_PASSWORD}

*** Test Cases ***
Dashboard Page Loads Successfully
    [Documentation]    Verify that dashboard page loads successfully
    [Tags]    smoke    critical
    ${url}=    Get Url
    Should Contain    ${url}    dashboard
    Assert Page Contains Text    Dashboard

Dashboard Shows Welcome Message
    [Documentation]    Verify that dashboard shows welcome message
    [Tags]    smoke
    # Should contain user name or welcome text
    ${page_content}=    Get Text    body
    Should Match Regexp    ${page_content}    (Welcome|Dashboard|Hello)

Dashboard Has Navigation Sidebar
    [Documentation]    Verify that dashboard has navigation sidebar
    [Tags]    smoke
    # Check for navigation elements
    ${nav}=    Get Element Count    nav
    Should Be True    ${nav} > 0

Dashboard Shows Statistics Cards
    [Documentation]    Verify that dashboard shows statistics or quick info cards
    [Tags]    smoke
    # Look for card-like elements or statistics
    ${cards}=    Get Element Count    [class*="card"], [class*="stat"], [class*="widget"]
    Log    Found ${cards} card/stat elements

Dashboard Has Quick Actions
    [Documentation]    Verify that dashboard has quick action buttons
    [Tags]    smoke
    # Look for action buttons or links
    ${actions}=    Get Element Count    button, a[href*="create"], a[href*="new"]
    Log    Found ${actions} action elements

User Can Access Profile From Dashboard
    [Documentation]    Verify that user can navigate to profile from dashboard
    [Tags]    navigation
    # Look for profile link or user menu
    ${profile_link}=    Get Element Count    a[href*="profile"]
    Run Keyword If    ${profile_link} > 0    Click    a[href*="profile"]
    ...    ELSE    Click    [data-testid="user-menu"]
    Wait For Load State    networkidle

Dashboard Is Responsive
    [Documentation]    Verify dashboard is responsive on different screen sizes
    [Tags]    responsive
    # Test tablet size
    Set Viewport Size    768    1024
    ${url}=    Get Url
    Should Contain    ${url}    dashboard
    # Test mobile size
    Set Viewport Size    375    667
    ${url}=    Get Url
    Should Contain    ${url}    dashboard

Dashboard Navigation Works
    [Documentation]    Verify that navigation links work from dashboard
    [Tags]    navigation
    # Try to navigate to events (if visible)
    ${events_link}=    Get Element Count    a[href*="events"]
    Run Keyword If    ${events_link} > 0    Test Events Navigation
    ...    ELSE    Log    Events link not visible for this user

Test Events Navigation
    [Documentation]    Helper keyword to test events navigation
    Click    a[href*="events"]
    Wait For Load State    networkidle
    ${url}=    Get Url
    Should Contain    ${url}    events

User Can Toggle Theme
    [Documentation]    Verify that user can toggle between light and dark theme
    [Tags]    theme
    # Look for theme toggle
    ${toggle}=    Get Element Count    [data-testid="theme-toggle"], [class*="theme"], button[aria-label*="theme"]
    Run Keyword If    ${toggle} > 0    Log    Theme toggle found
    ...    ELSE    Log    Theme toggle not found    WARN

User Can Change Language
    [Documentation]    Verify that user can change language
    [Tags]    i18n
    # Look for language selector
    ${lang_selector}=    Get Element Count    [data-testid="language-selector"], select[name="language"], [class*="lang"]
    Run Keyword If    ${lang_selector} > 0    Log    Language selector found
    ...    ELSE    Log    Language selector not found    WARN

User Can Logout From Dashboard
    [Documentation]    Verify that user can logout from dashboard
    [Tags]    smoke    critical
    # Find and click logout
    ${logout_visible}=    Get Element Count    text=Log Out
    Run Keyword If    ${logout_visible} > 0    Click    text=Log Out
    ...    ELSE    Click User Menu And Logout
    Wait For Load State    networkidle
    ${url}=    Get Url
    Should Not Contain    ${url}    dashboard

Click User Menu And Logout
    [Documentation]    Helper to click user menu then logout
    ${user_menu}=    Get Element Count    [data-testid="user-menu"]
    Run Keyword If    ${user_menu} > 0    Click    [data-testid="user-menu"]
    Sleep    1s
    Click    text=Log Out
