*** Settings ***
Documentation     End-to-End Event Participation Flow Tests
...               Complete user journey for event creation, viewing, and participation
Resource          ../resources/common.resource
Suite Setup       Setup Event E2E Tests
Suite Teardown    Teardown Event E2E Tests
Test Setup        Open Browser And Login As Organizer
Test Teardown     Close Browser Session
Test Tags         e2e    events    critical

*** Variables ***
${ORGANIZER_EMAIL}        ${EMPTY}
${PARTICIPANT_EMAIL}      ${EMPTY}
${TEST_PASSWORD}          SecurePassword123!
${TEST_EVENT_UUID}        ${EMPTY}

*** Keywords ***
Setup Event E2E Tests
    [Documentation]    Create test users for event testing
    Create Session    api    ${API_URL}    verify=${False}
    # Create organizer user
    ${random}=    String.Generate Random String    8    [LOWER]
    Set Suite Variable    ${ORGANIZER_EMAIL}    organizer_${random}@example.com
    Set Suite Variable    ${PARTICIPANT_EMAIL}    participant_${random}@example.com
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    # Register organizer
    ${data}=    Create Dictionary
    ...    first_name=Event
    ...    last_name=Organizer
    ...    email=${ORGANIZER_EMAIL}
    ...    password=${TEST_PASSWORD}
    ...    password_confirmation=${TEST_PASSWORD}
    ...    birth_date=1990-01-01
    POST On Session    api    /register    json=${data}    headers=${headers}    expected_status=any
    # Register participant
    ${data}=    Create Dictionary
    ...    first_name=Event
    ...    last_name=Participant
    ...    email=${PARTICIPANT_EMAIL}
    ...    password=${TEST_PASSWORD}
    ...    password_confirmation=${TEST_PASSWORD}
    ...    birth_date=1992-03-15
    POST On Session    api    /register    json=${data}    headers=${headers}    expected_status=any
    # Setup permissions in database
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email IN ('${ORGANIZER_EMAIL}', '${PARTICIPANT_EMAIL}')
    # Get user IDs
    ${organizer_id}=    Query    SELECT id FROM users WHERE email = '${ORGANIZER_EMAIL}'
    ${participant_id}=    Query    SELECT id FROM users WHERE email = '${PARTICIPANT_EMAIL}'
    # Assign event permissions to organizer
    ${create_perm}=    Query    SELECT id FROM permissions WHERE name = 'create events'
    ${edit_perm}=    Query    SELECT id FROM permissions WHERE name = 'edit events'
    ${delete_perm}=    Query    SELECT id FROM permissions WHERE name = 'delete events'
    ${view_perm}=    Query    SELECT id FROM permissions WHERE name = 'view events'
    Run Keyword If    ${create_perm}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${create_perm}[0][0], 'App\\\\Models\\\\User', ${organizer_id}[0][0])
    Run Keyword If    ${edit_perm}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${edit_perm}[0][0], 'App\\\\Models\\\\User', ${organizer_id}[0][0])
    Run Keyword If    ${delete_perm}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${delete_perm}[0][0], 'App\\\\Models\\\\User', ${organizer_id}[0][0])
    Run Keyword If    ${view_perm}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${view_perm}[0][0], 'App\\\\Models\\\\User', ${organizer_id}[0][0])
    # Assign view permission to participant
    Run Keyword If    ${view_perm}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${view_perm}[0][0], 'App\\\\Models\\\\User', ${participant_id}[0][0])
    Disconnect From Test Database
    Delete All Sessions

Teardown Event E2E Tests
    [Documentation]    Cleanup test users and events
    Connect To Test Database
    Execute Sql String    DELETE FROM event_user WHERE event_id IN (SELECT id FROM events WHERE title LIKE 'E2E Test%')
    Execute Sql String    DELETE FROM events WHERE title LIKE 'E2E Test%'
    Clean Test Data    users    email    ${ORGANIZER_EMAIL}
    Clean Test Data    users    email    ${PARTICIPANT_EMAIL}
    Disconnect From Test Database

Open Browser And Login As Organizer
    [Documentation]    Opens browser and logs in as organizer
    Open Browser To Application    ${BASE_URL}/login
    Login As User    ${ORGANIZER_EMAIL}    ${TEST_PASSWORD}

Open Browser And Login As Participant
    [Documentation]    Opens browser and logs in as participant
    Open Browser To Application    ${BASE_URL}/login
    Login As User    ${PARTICIPANT_EMAIL}    ${TEST_PASSWORD}

Create Test Event Via UI
    [Documentation]    Creates a test event through the UI
    [Arguments]    ${title}
    Go To    ${BASE_URL}/events/create
    Wait For Load State    networkidle
    # Fill event form
    Fill Text    input[name="title"]    ${title}
    ${description_field}=    Get Element Count    textarea[name="description"]
    Run Keyword If    ${description_field} > 0    Fill Text    textarea[name="description"]    E2E test event description
    # Set dates
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    Fill Text    input[name="start_date"]    ${start_date}
    Fill Text    input[name="end_date"]    ${end_date}
    # Location if available
    ${location_field}=    Get Element Count    input[name="location"]
    Run Keyword If    ${location_field} > 0    Fill Text    input[name="location"]    Test Location
    # Submit
    Click    button[type="submit"]
    Wait For Load State    networkidle

*** Test Cases ***
Complete Event Creation To Participation Flow
    [Documentation]    Full flow from event creation to participant joining
    [Tags]    smoke    critical
    # Step 1: Organizer creates event
    ${event_title}=    Set Variable    E2E Test Event Flow
    Create Test Event Via UI    ${event_title}

    # Step 2: Verify event created
    Connect To Test Database
    Verify Record Exists In Database    events    title    ${event_title}
    ${result}=    Query    SELECT uuid FROM events WHERE title = '${event_title}'
    ${event_uuid}=    Set Variable    ${result}[0][0]
    # Make event public
    Execute Sql String    UPDATE events SET is_public = 1 WHERE uuid = '${event_uuid}'
    Disconnect From Test Database

    # Step 3: Logout organizer
    Go To    ${BASE_URL}/logout
    Wait For Load State    networkidle
    Close Browser Session

    # Step 4: Login as participant
    Open Browser And Login As Participant

    # Step 5: Navigate to events
    Go To    ${BASE_URL}/events
    Wait For Load State    networkidle
    Assert Page Contains Text    ${event_title}

    # Step 6: View event details
    Go To    ${BASE_URL}/events/${event_uuid}
    Wait For Load State    networkidle

    # Step 7: Join event
    ${join_button}=    Get Element Count    text=Join
    Run Keyword If    ${join_button} > 0    Click    text=Join
    ...    ELSE    Log    Join button not found    WARN
    Wait For Load State    networkidle

    # Step 8: Verify participation
    Connect To Test Database
    ${participant_id}=    Query    SELECT id FROM users WHERE email = '${PARTICIPANT_EMAIL}'
    ${event_id}=    Query    SELECT id FROM events WHERE uuid = '${event_uuid}'
    ${participation}=    Query    SELECT COUNT(*) FROM event_user WHERE event_id = ${event_id}[0][0] AND user_id = ${participant_id}[0][0]
    Disconnect From Test Database
    Should Be True    ${participation}[0][0] > 0    User should be a participant

Organizer Can Edit Their Event
    [Documentation]    Verify organizer can edit their own event
    [Tags]    smoke
    # Create event
    ${event_title}=    Set Variable    E2E Test Edit Event
    Create Test Event Via UI    ${event_title}

    # Get event UUID
    Connect To Test Database
    ${result}=    Query    SELECT uuid FROM events WHERE title = '${event_title}'
    ${event_uuid}=    Set Variable    ${result}[0][0]
    Disconnect From Test Database

    # Navigate to edit page
    Go To    ${BASE_URL}/events/${event_uuid}/edit
    Wait For Load State    networkidle

    # Update title
    Fill Text    input[name="title"]    E2E Test Updated Event
    Click    button[type="submit"]
    Wait For Load State    networkidle

    # Verify update
    Connect To Test Database
    Verify Record Exists In Database    events    title    E2E Test Updated Event
    Disconnect From Test Database

Participant Can Leave Joined Event
    [Documentation]    Verify participant can leave an event they joined
    [Tags]    smoke
    # Create public event
    ${event_title}=    Set Variable    E2E Test Leave Event
    Create Test Event Via UI    ${event_title}

    Connect To Test Database
    ${result}=    Query    SELECT uuid, id FROM events WHERE title = '${event_title}'
    ${event_uuid}=    Set Variable    ${result}[0][0]
    ${event_id}=    Set Variable    ${result}[0][1]
    Execute Sql String    UPDATE events SET is_public = 1 WHERE uuid = '${event_uuid}'
    # Add participant directly
    ${participant_id}=    Query    SELECT id FROM users WHERE email = '${PARTICIPANT_EMAIL}'
    Execute Sql String    INSERT INTO event_user (event_id, user_id, created_at, updated_at) VALUES (${event_id}, ${participant_id}[0][0], NOW(), NOW())
    Disconnect From Test Database

    # Logout and login as participant
    Go To    ${BASE_URL}/logout
    Wait For Load State    networkidle
    Close Browser Session
    Open Browser And Login As Participant

    # View event
    Go To    ${BASE_URL}/events/${event_uuid}
    Wait For Load State    networkidle

    # Leave event
    ${leave_button}=    Get Element Count    text=Leave
    Run Keyword If    ${leave_button} > 0    Click    text=Leave
    ...    ELSE    Log    Leave button not found    WARN
    Wait For Load State    networkidle

    # Verify left
    Connect To Test Database
    ${participation}=    Query    SELECT COUNT(*) FROM event_user WHERE event_id = ${event_id} AND user_id = ${participant_id}[0][0]
    Disconnect From Test Database
    Should Be Equal As Integers    ${participation}[0][0]    0

User Cannot Join Same Event Twice
    [Documentation]    Verify user cannot join the same event twice
    [Tags]    negative
    # Create public event
    ${event_title}=    Set Variable    E2E Test Double Join
    Create Test Event Via UI    ${event_title}

    Connect To Test Database
    ${result}=    Query    SELECT uuid FROM events WHERE title = '${event_title}'
    ${event_uuid}=    Set Variable    ${result}[0][0]
    Execute Sql String    UPDATE events SET is_public = 1 WHERE uuid = '${event_uuid}'
    Disconnect From Test Database

    # Logout and login as participant
    Go To    ${BASE_URL}/logout
    Wait For Load State    networkidle
    Close Browser Session
    Open Browser And Login As Participant

    # Join event first time
    Go To    ${BASE_URL}/events/${event_uuid}
    Wait For Load State    networkidle
    ${join_button}=    Get Element Count    text=Join
    Run Keyword If    ${join_button} > 0    Click    text=Join
    Wait For Load State    networkidle

    # Try to join again (should show leave or be disabled)
    Go To    ${BASE_URL}/events/${event_uuid}
    Wait For Load State    networkidle

    # Verify only one participation
    Connect To Test Database
    ${event_id}=    Query    SELECT id FROM events WHERE uuid = '${event_uuid}'
    ${participant_id}=    Query    SELECT id FROM users WHERE email = '${PARTICIPANT_EMAIL}'
    ${count}=    Query    SELECT COUNT(*) FROM event_user WHERE event_id = ${event_id}[0][0] AND user_id = ${participant_id}[0][0]
    Disconnect From Test Database
    Should Be Equal As Integers    ${count}[0][0]    1

Event Search Works Correctly
    [Documentation]    Verify event search functionality
    [Tags]    search
    # Create events with specific titles
    ${unique}=    String.Generate Random String    8
    Create Test Event Via UI    E2E Test Searchable ${unique}

    # Search for event
    Go To    ${BASE_URL}/events?search=${unique}
    Wait For Load State    networkidle

    # Verify search results
    Assert Page Contains Text    ${unique}

Event List Shows Only Published Events To Participant
    [Documentation]    Verify event list filtering
    [Tags]    authorization
    # Create draft event
    ${event_title}=    Set Variable    E2E Test Draft Event
    Create Test Event Via UI    ${event_title}

    # Keep as draft
    Connect To Test Database
    Execute Sql String    UPDATE events SET status = 'draft' WHERE title = '${event_title}'
    Disconnect From Test Database

    # Logout and login as participant
    Go To    ${BASE_URL}/logout
    Wait For Load State    networkidle
    Close Browser Session
    Open Browser And Login As Participant

    # Check events list
    Go To    ${BASE_URL}/events
    Wait For Load State    networkidle

    # Draft event should not be visible
    ${page_content}=    Get Text    body
    Should Not Contain    ${page_content}    ${event_title}
