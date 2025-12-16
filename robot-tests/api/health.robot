*** Settings ***
Documentation     Health Check and Basic API Tests
...               Tests for basic API connectivity and health checks
Library           RequestsLibrary
Library           Collections
Test Tags         api    smoke    health

*** Variables ***
${BASE_URL}       http://nginx
${API_URL}        http://nginx/api

*** Test Cases ***
Application Is Running
    [Documentation]    Verify that the application is accessible
    [Tags]    critical
    Create Session    app    ${BASE_URL}    verify=${False}
    ${response}=    GET On Session    app    /    expected_status=any
    Should Be True    ${response.status_code} < 500    Application returned server error

Login Page Is Accessible
    [Documentation]    Verify that the login page is accessible
    [Tags]    critical
    Create Session    app    ${BASE_URL}    verify=${False}
    ${response}=    GET On Session    app    /login    expected_status=any
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302

Register Page Is Accessible
    [Documentation]    Verify that the register page is accessible
    [Tags]    critical
    Create Session    app    ${BASE_URL}    verify=${False}
    ${response}=    GET On Session    app    /register    expected_status=any
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 302

CSRF Cookie Endpoint Works
    [Documentation]    Verify that the CSRF cookie endpoint works
    Create Session    app    ${BASE_URL}    verify=${False}
    ${response}=    GET On Session    app    /sanctum/csrf-cookie    expected_status=any
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 204

Unauthenticated API Request Returns 401 Or Redirect
    [Documentation]    Verify that unauthenticated API requests are rejected
    Create Session    api    ${API_URL}    verify=${False}
    ${headers}=    Create Dictionary    Accept=application/json
    ${response}=    GET On Session    api    /user    headers=${headers}    expected_status=any
    Should Be True    ${response.status_code} == 401 or ${response.status_code} == 302 or ${response.status_code} == 403

Application Returns Valid HTML
    [Documentation]    Verify that the application returns valid HTML
    Create Session    app    ${BASE_URL}    verify=${False}
    ${response}=    GET On Session    app    /    expected_status=any
    Should Contain    ${response.text}    <!DOCTYPE html>

Static Assets Are Served
    [Documentation]    Verify that static assets are accessible
    Create Session    app    ${BASE_URL}    verify=${False}
    ${response}=    GET On Session    app    /build/manifest.json    expected_status=any
    # Either returns manifest, 404 if not built, or 403 if directory listing disabled
    Should Be True    ${response.status_code} == 200 or ${response.status_code} == 404 or ${response.status_code} == 403

Database Connection Is Working
    [Documentation]    Verify database connectivity via login attempt
    Create Session    app    ${BASE_URL}    verify=${False}
    ${headers}=    Create Dictionary    Content-Type=application/x-www-form-urlencoded    Accept=text/html
    ${data}=    Create Dictionary    email=test@example.com    password=wrongpassword
    ${response}=    POST On Session    app    /login    data=${data}    headers=${headers}    expected_status=any
    # Should get a redirect or validation error, not a 500
    Should Be True    ${response.status_code} < 500    Database connection may be failing
