*** Settings ***
Documentation     API-specific keywords for ICC Munich tests
Library           RequestsLibrary
Library           Collections
Library           JSONLibrary
Resource          common.resource

*** Variables ***
${API_TIMEOUT}              30

*** Keywords ***
# Authentication API Keywords
API Register User
    [Documentation]    Registers a new user via API
    [Arguments]    ${first_name}    ${last_name}    ${email}    ${password}    ${birth_date}=1990-01-01
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary
    ...    first_name=${first_name}
    ...    last_name=${last_name}
    ...    email=${email}
    ...    password=${password}
    ...    password_confirmation=${password}
    ...    birth_date=${birth_date}
    ${response}=    POST On Session    api    /register    json=${data}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Logout
    [Documentation]    Logs out the current user via API
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    POST On Session    api    /logout    headers=${headers}    expected_status=any
    RETURN    ${response}

# User API Keywords
API Get Current User
    [Documentation]    Gets the currently authenticated user
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /user    headers=${headers}
    RETURN    ${response}

API Update User Profile
    [Documentation]    Updates the user profile
    [Arguments]    ${data}
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${response}=    PATCH On Session    api    /profile    json=${data}    headers=${headers}    expected_status=any
    RETURN    ${response}

# Events API Keywords
API Get Events
    [Documentation]    Gets the list of events
    [Arguments]    ${params}=${None}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /events    params=${params}    headers=${headers}
    RETURN    ${response}

API Get Event By UUID
    [Documentation]    Gets a specific event by UUID
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /events/${uuid}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Create Event
    [Documentation]    Creates a new event
    [Arguments]    ${title}    ${description}    ${start_date}    ${end_date}    ${location}=${None}
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary
    ...    title=${title}
    ...    description=${description}
    ...    start_date=${start_date}
    ...    end_date=${end_date}
    Run Keyword If    '${location}' != '${None}'    Set To Dictionary    ${data}    location=${location}
    ${response}=    POST On Session    api    /events    json=${data}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Update Event
    [Documentation]    Updates an existing event
    [Arguments]    ${uuid}    ${data}
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${response}=    PUT On Session    api    /events/${uuid}    json=${data}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Delete Event
    [Documentation]    Deletes an event
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    DELETE On Session    api    /events/${uuid}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Join Event
    [Documentation]    Joins an event
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    POST On Session    api    /events/${uuid}/join    headers=${headers}    expected_status=any
    RETURN    ${response}

API Leave Event
    [Documentation]    Leaves an event
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    DELETE On Session    api    /events/${uuid}/leave    headers=${headers}    expected_status=any
    RETURN    ${response}

# Books API Keywords
API Get Books
    [Documentation]    Gets the list of books
    [Arguments]    ${params}=${None}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /books    params=${params}    headers=${headers}
    RETURN    ${response}

API Get Book By UUID
    [Documentation]    Gets a specific book by UUID
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /books/${uuid}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Rent Book
    [Documentation]    Rents a book
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    POST On Session    api    /books/${uuid}/rent    headers=${headers}    expected_status=any
    RETURN    ${response}

API Return Book
    [Documentation]    Returns a rented book
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    POST On Session    api    /books/${uuid}/return    headers=${headers}    expected_status=any
    RETURN    ${response}

# Articles API Keywords
API Get Articles
    [Documentation]    Gets the list of articles
    [Arguments]    ${params}=${None}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /articles    params=${params}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Get Article By UUID
    [Documentation]    Gets a specific article by UUID
    [Arguments]    ${uuid}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /articles/${uuid}    headers=${headers}    expected_status=any
    RETURN    ${response}

API Create Article
    [Documentation]    Creates a new article
    [Arguments]    ${title}    ${content}    ${status}=draft
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary
    ...    title=${title}
    ...    content=${content}
    ...    status=${status}
    ${response}=    POST On Session    api    /articles    json=${data}    headers=${headers}    expected_status=any
    RETURN    ${response}

# Chat API Keywords
API Get Chat Rooms
    [Documentation]    Gets the list of chat rooms
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /chat/rooms    headers=${headers}    expected_status=any
    RETURN    ${response}

API Get Chat Messages
    [Documentation]    Gets messages from a chat room
    [Arguments]    ${room_id}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /chat/rooms/${room_id}/messages    headers=${headers}    expected_status=any
    RETURN    ${response}

API Send Chat Message
    [Documentation]    Sends a message to a chat room
    [Arguments]    ${room_id}    ${message}
    ${headers}=    Create Dictionary    Content-Type=application/json    Accept=application/json
    ${data}=    Create Dictionary    message=${message}
    ${response}=    POST On Session    api    /chat/rooms/${room_id}/messages    json=${data}    headers=${headers}    expected_status=any
    RETURN    ${response}

# Validation Keywords
Assert Response Status
    [Documentation]    Asserts the response status code
    [Arguments]    ${response}    ${expected_status}
    Should Be Equal As Strings    ${response.status_code}    ${expected_status}

Assert Response Contains Key
    [Documentation]    Asserts that the response JSON contains a specific key
    [Arguments]    ${response}    ${key}
    ${json}=    Set Variable    ${response.json()}
    Dictionary Should Contain Key    ${json}    ${key}

Assert Response Key Value
    [Documentation]    Asserts that a response JSON key has a specific value
    [Arguments]    ${response}    ${key}    ${expected_value}
    ${json}=    Set Variable    ${response.json()}
    ${actual_value}=    Get From Dictionary    ${json}    ${key}
    Should Be Equal    ${actual_value}    ${expected_value}

Assert Response Is List
    [Documentation]    Asserts that the response is a list
    [Arguments]    ${response}
    ${json}=    Set Variable    ${response.json()}
    ${type}=    Evaluate    type($json).__name__
    Should Be Equal    ${type}    list

Assert Response List Not Empty
    [Documentation]    Asserts that the response list is not empty
    [Arguments]    ${response}
    ${json}=    Set Variable    ${response.json()}
    ${length}=    Get Length    ${json}
    Should Be True    ${length} > 0

Assert Response Has Pagination
    [Documentation]    Asserts that the response has pagination structure
    [Arguments]    ${response}
    ${json}=    Set Variable    ${response.json()}
    Dictionary Should Contain Key    ${json}    data
    Dictionary Should Contain Key    ${json}    meta
    Dictionary Should Contain Key    ${json}    links
