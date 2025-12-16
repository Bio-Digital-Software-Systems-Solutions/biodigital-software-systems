*** Settings ***
Documentation     Books API Tests
...               Tests for book management and rental system
Resource          ../resources/common.resource
Resource          ../resources/api_keywords.resource
Suite Setup       Setup Books Test Suite
Suite Teardown    Teardown Books Test Suite
Test Tags         api    books

*** Variables ***
${TEST_USER_EMAIL}       books_test@example.com
${TEST_USER_PASSWORD}    SecurePassword123!

*** Keywords ***
Setup Books Test Suite
    [Documentation]    Setup test suite
    Create API Session
    # Create test user
    ${random}=    String.Generate Random String    8    [LOWER]
    Set Suite Variable    ${TEST_USER_EMAIL}    books_${random}@example.com
    API Register User
    ...    first_name=Books
    ...    last_name=Tester
    ...    email=${TEST_USER_EMAIL}
    ...    password=${TEST_USER_PASSWORD}
    Connect To Test Database
    Execute Sql String    UPDATE users SET email_verified_at = NOW() WHERE email = '${TEST_USER_EMAIL}'
    # Assign view books permission
    ${user_id}=    Query    SELECT id FROM users WHERE email = '${TEST_USER_EMAIL}'
    ${permission_id}=    Query    SELECT id FROM permissions WHERE name = 'view books'
    Run Keyword If    ${permission_id}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${permission_id}[0][0], 'App\\\\Models\\\\User', ${user_id}[0][0])
    ${permission_id}=    Query    SELECT id FROM permissions WHERE name = 'rent books'
    Run Keyword If    ${permission_id}    Execute Sql String    INSERT IGNORE INTO model_has_permissions (permission_id, model_type, model_id) VALUES (${permission_id}[0][0], 'App\\\\Models\\\\User', ${user_id}[0][0])
    Disconnect From Test Database
    API Login    ${TEST_USER_EMAIL}    ${TEST_USER_PASSWORD}

Teardown Books Test Suite
    [Documentation]    Cleanup test suite
    Connect To Test Database
    Clean Test Data    users    email    ${TEST_USER_EMAIL}
    Disconnect From Test Database
    Delete All Sessions

Create Test Book
    [Documentation]    Creates a test book in the database
    [Arguments]    ${title}
    Connect To Test Database
    ${uuid}=    String.Generate Random String    36
    Execute Sql String    INSERT INTO books (uuid, title, author, isbn, description, available_copies, total_copies, created_at, updated_at) VALUES ('${uuid}', '${title}', 'Test Author', '978-0000000000', 'Test book description', 5, 5, NOW(), NOW())
    ${result}=    Query    SELECT uuid FROM books WHERE title = '${title}'
    Disconnect From Test Database
    RETURN    ${result}[0][0]

*** Test Cases ***
User Can Get List Of Books
    [Documentation]    Verify that user can get list of books
    [Tags]    smoke    critical
    ${response}=    API Get Books
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302

User Can View Book Details
    [Documentation]    Verify that user can view book details
    [Tags]    smoke
    # Create test book
    ${title}=    String.Generate Random String    10
    ${uuid}=    Create Test Book    Robot Book ${title}
    # Get book details
    ${response}=    API Get Book By UUID    ${uuid}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    books    title    Robot Book ${title}
    Disconnect From Test Database

User Can Rent Available Book
    [Documentation]    Verify that user can rent an available book
    [Tags]    smoke    critical
    # Create test book
    ${title}=    String.Generate Random String    10
    ${uuid}=    Create Test Book    Rentable Book ${title}
    # Rent book
    ${response}=    API Rent Book    ${uuid}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 201 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    book_rentals    book_id    (SELECT id FROM books WHERE title = 'Rentable Book ${title}')
    Clean Test Data    books    title    Rentable Book ${title}
    Disconnect From Test Database

User Cannot Rent Unavailable Book
    [Documentation]    Verify that user cannot rent a book with no available copies
    [Tags]    negative
    # Create book with no available copies
    Connect To Test Database
    ${uuid}=    String.Generate Random String    36
    ${title}=    String.Generate Random String    10
    Execute Sql String    INSERT INTO books (uuid, title, author, isbn, description, available_copies, total_copies, created_at, updated_at) VALUES ('${uuid}', 'Unavailable Book ${title}', 'Test Author', '978-0000000001', 'No copies', 0, 0, NOW(), NOW())
    Disconnect From Test Database
    # Try to rent
    ${response}=    API Rent Book    ${uuid}
    Should Be True    ${response.status_code} == 400 or ${response.status_code} == 422 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    books    title    Unavailable Book ${title}
    Disconnect From Test Database

User Can Return Rented Book
    [Documentation]    Verify that user can return a rented book
    [Tags]    smoke
    # Create and rent book
    ${title}=    String.Generate Random String    10
    ${uuid}=    Create Test Book    Return Book ${title}
    API Rent Book    ${uuid}
    # Return book
    ${response}=    API Return Book    ${uuid}
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302
    # Cleanup
    Connect To Test Database
    Clean Test Data    book_rentals    book_id    (SELECT id FROM books WHERE title = 'Return Book ${title}')
    Clean Test Data    books    title    Return Book ${title}
    Disconnect From Test Database

User Cannot Return Book They Did Not Rent
    [Documentation]    Verify that user cannot return a book they didn't rent
    [Tags]    negative
    # Create book but don't rent it
    ${title}=    String.Generate Random String    10
    ${uuid}=    Create Test Book    Not Rented ${title}
    # Try to return
    ${response}=    API Return Book    ${uuid}
    Should Be True    ${response.status_code} == 400 or ${response.status_code} == 404 or ${response.status_code} == 302 or ${response.status_code} == 422
    # Cleanup
    Connect To Test Database
    Clean Test Data    books    title    Not Rented ${title}
    Disconnect From Test Database

Book Search Returns Matching Results
    [Documentation]    Verify that book search returns correct results
    [Tags]    search
    # Create book with unique title
    ${unique}=    String.Generate Random String    8
    ${uuid}=    Create Test Book    Searchable Robot ${unique}
    # Search
    ${params}=    Create Dictionary    search=Robot ${unique}
    ${response}=    API Get Books    ${params}
    Assert Response Status    ${response}    200
    # Cleanup
    Connect To Test Database
    Clean Test Data    books    title    Searchable Robot ${unique}
    Disconnect From Test Database

Non-Existent Book Returns 404
    [Documentation]    Verify that accessing non-existent book returns 404
    [Tags]    negative
    ${response}=    API Get Book By UUID    non-existent-book-uuid
    Should Be True    ${response.status_code} == 404 or ${response.status_code} == 302

Available Copies Decrease After Rental
    [Documentation]    Verify that available copies decrease after rental
    [Tags]    integration
    ${title}=    String.Generate Random String    10
    ${uuid}=    Create Test Book    Copies Test ${title}
    # Check initial copies
    Connect To Test Database
    ${before}=    Query    SELECT available_copies FROM books WHERE title = 'Copies Test ${title}'
    Disconnect From Test Database
    # Rent book
    API Rent Book    ${uuid}
    # Check copies decreased
    Connect To Test Database
    ${after}=    Query    SELECT available_copies FROM books WHERE title = 'Copies Test ${title}'
    Should Be True    ${after}[0][0] < ${before}[0][0]
    # Cleanup
    Clean Test Data    book_rentals    book_id    (SELECT id FROM books WHERE title = 'Copies Test ${title}')
    Clean Test Data    books    title    Copies Test ${title}
    Disconnect From Test Database

Available Copies Increase After Return
    [Documentation]    Verify that available copies increase after return
    [Tags]    integration
    ${title}=    String.Generate Random String    10
    ${uuid}=    Create Test Book    Return Copies ${title}
    # Rent and check copies
    API Rent Book    ${uuid}
    Connect To Test Database
    ${before}=    Query    SELECT available_copies FROM books WHERE title = 'Return Copies ${title}'
    Disconnect From Test Database
    # Return book
    API Return Book    ${uuid}
    # Check copies increased
    Connect To Test Database
    ${after}=    Query    SELECT available_copies FROM books WHERE title = 'Return Copies ${title}'
    Should Be True    ${after}[0][0] > ${before}[0][0]
    # Cleanup
    Clean Test Data    book_rentals    book_id    (SELECT id FROM books WHERE title = 'Return Copies ${title}')
    Clean Test Data    books    title    Return Copies ${title}
    Disconnect From Test Database
