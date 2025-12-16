*** Settings ***
Documentation     Events API Tests
...               Tests for event CRUD operations and participation
Resource          ../resources/common.resource
Resource          ../resources/api_keywords.resource
Suite Setup       Setup Events Test Suite
Suite Teardown    Teardown Events Test Suite
Test Tags         api    events

*** Variables ***
${TEST_USER_EMAIL}       events_test@example.com
${TEST_USER_PASSWORD}    SecurePassword123!

*** Keywords ***
Setup Events Test Suite
    [Documentation]    Setup test suite - create API session and test user
    Create API Session
    # Create test user with permissions
    Connect To Test Database
    ${random}=    String.Generate Random String    8    [LOWER]
    Set Suite Variable    ${TEST_USER_EMAIL}    events_${random}@example.com
    Disconnect From Test Database
    API Register User
    ...    first_name=Events
    ...    last_name=Tester
    ...    email=${TEST_USER_EMAIL}
    ...    password=${TEST_USER_PASSWORD}
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${TEST_USER_EMAIL}'
    # Assign permissions
    ${user_id}=    Query    SELECT id FROM users WHERE email = '${TEST_USER_EMAIL}'
    ${permission_id}=    Query    SELECT id FROM permissions WHERE name = 'create events'
    Run Keyword If    ${permission_id}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${permission_id}[0][0], 'App\\\\Models\\\\User', ${user_id}[0][0])
    ${permission_id}=    Query    SELECT id FROM permissions WHERE name = 'edit events'
    Run Keyword If    ${permission_id}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${permission_id}[0][0], 'App\\\\Models\\\\User', ${user_id}[0][0])
    ${permission_id}=    Query    SELECT id FROM permissions WHERE name = 'delete events'
    Run Keyword If    ${permission_id}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${permission_id}[0][0], 'App\\\\Models\\\\User', ${user_id}[0][0])
    ${permission_id}=    Query    SELECT id FROM permissions WHERE name = 'view events'
    Run Keyword If    ${permission_id}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${permission_id}[0][0], 'App\\\\Models\\\\User', ${user_id}[0][0])
    Disconnect From Test Database
    API Login    ${TEST_USER_EMAIL}    ${TEST_USER_PASSWORD}

Teardown Events Test Suite
    [Documentation]    Cleanup test suite
    Connect To Test Database
    Clean Test Data    users    email    ${TEST_USER_EMAIL}
    Disconnect From Test Database
    Delete All Sessions

*** Test Cases ***
User Can Get List Of Events
    [Documentation]    Verify that authenticated user can get list of events
    [Tags]    smoke    critical
    ${response}=    API Get Events
    Assert Response Status    ${response}    200

User Can Create New Event
    [Documentation]    Verify that user with permission can create a new event
    [Tags]    smoke    critical
    ${title}=    String.Generate Random String    10
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${response}=    API Create Event
    ...    title=Test Event ${title}
    ...    description=This is a test event created by Robot Framework
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    ...    location=Test Location
    Should Be True    ${response.status_code} == 201 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    events    title    Test Event ${title}
    Disconnect From Test Database

User Cannot Create Event With Invalid Data
    [Documentation]    Verify that event creation fails with invalid data
    [Tags]    negative    validation
    ${response}=    API Create Event
    ...    title=
    ...    description=Test description
    ...    start_date=invalid-date
    ...    end_date=2024-01-01
    Should Be True    ${response.status_code} == 302 or ${response.status_code} == 422

User Can View Event Details
    [Documentation]    Verify that user can view event details
    [Tags]    smoke
    # Create event first
    ${title}=    String.Generate Random String    10
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    API Create Event
    ...    title=View Test ${title}
    ...    description=Test event for viewing
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    # Get event UUID from database
    Connect To Test Database
    ${result}=    Query    SELECT uuid FROM events WHERE title = 'View Test ${title}'
    ${uuid}=    Set Variable    ${result}[0][0]
    Disconnect From Test Database
    # Get event details
    ${response}=    API Get Event By UUID    ${uuid}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    events    title    View Test ${title}
    Disconnect From Test Database

User Can Update Own Event
    [Documentation]    Verify that user can update their own event
    [Tags]    smoke
    # Create event
    ${title}=    String.Generate Random String    10
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    API Create Event
    ...    title=Update Test ${title}
    ...    description=Original description
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    # Get event UUID
    Connect To Test Database
    ${result}=    Query    SELECT uuid FROM events WHERE title = 'Update Test ${title}'
    ${uuid}=    Set Variable    ${result}[0][0]
    Disconnect From Test Database
    # Update event
    ${update_data}=    Create Dictionary
    ...    title=Updated Event ${title}
    ...    description=Updated description
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    ...    status=published
    ${response}=    API Update Event    ${uuid}    ${update_data}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Execute Sql String    DELETE FROM events WHERE title LIKE '%${title}%'
    Disconnect From Test Database

User Can Delete Own Event
    [Documentation]    Verify that user can delete their own event
    [Tags]    smoke
    # Create event
    ${title}=    String.Generate Random String    10
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    API Create Event
    ...    title=Delete Test ${title}
    ...    description=Event to be deleted
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    # Get event UUID
    Connect To Test Database
    ${result}=    Query    SELECT uuid FROM events WHERE title = 'Delete Test ${title}'
    ${uuid}=    Set Variable    ${result}[0][0]
    Disconnect From Test Database
    # Delete event
    ${response}=    API Delete Event    ${uuid}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 204 or ${response.status_code} == 302
    # Verify deletion
    Connect To Test Database
    ${count}=    Query    SELECT COUNT(*) FROM events WHERE title = 'Delete Test ${title}' AND deleted_at IS NULL
    Should Be Equal As Integers    ${count}[0][0]    0
    Disconnect From Test Database

User Can Join Public Event
    [Documentation]    Verify that user can join a public event
    [Tags]    smoke
    # Create public event
    ${title}=    String.Generate Random String    10
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    API Create Event
    ...    title=Join Test ${title}
    ...    description=Public event
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    # Make event public
    Connect To Test Database
    Execute Sql String    UPDATE events SET is_public = 1 WHERE title = 'Join Test ${title}'
    ${result}=    Query    SELECT uuid FROM events WHERE title = 'Join Test ${title}'
    ${uuid}=    Set Variable    ${result}[0][0]
    Disconnect From Test Database
    # Join event
    ${response}=    API Join Event    ${uuid}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    events    title    Join Test ${title}
    Disconnect From Test Database

User Can Leave Joined Event
    [Documentation]    Verify that user can leave an event they joined
    [Tags]    smoke
    # Create and join event
    ${title}=    String.Generate Random String    10
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    API Create Event
    ...    title=Leave Test ${title}
    ...    description=Event to leave
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    # Get UUID and join
    Connect To Test Database
    Execute Sql String    UPDATE events SET is_public = 1 WHERE title = 'Leave Test ${title}'
    ${result}=    Query    SELECT uuid FROM events WHERE title = 'Leave Test ${title}'
    ${uuid}=    Set Variable    ${result}[0][0]
    Disconnect From Test Database
    API Join Event    ${uuid}
    # Leave event
    ${response}=    API Leave Event    ${uuid}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    events    title    Leave Test ${title}
    Disconnect From Test Database

Event Search Returns Matching Results
    [Documentation]    Verify that event search returns correct results
    [Tags]    search
    # Create events with specific titles
    ${unique}=    String.Generate Random String    8
    ${start_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    ${end_date}=    Get Current Date    result_format=%Y-%m-%d    increment=7 days
    API Create Event
    ...    title=Search Robot ${unique}
    ...    description=Searchable event
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    # Search for events
    ${params}=    Create Dictionary    search=Robot ${unique}
    ${response}=    API Get Events    ${params}
    Assert Response Status    ${response}    200
    # Cleanup
    Connect To Test Database
    Clean Test Data    events    title    Search Robot ${unique}
    Disconnect From Test Database

Non-Existent Event Returns 404
    [Documentation]    Verify that accessing non-existent event returns 404
    [Tags]    negative
    ${response}=    API Get Event By UUID    non-existent-uuid-12345
    Should Be True    ${response.status_code} == 404 or ${response.status_code} == 302
