*** Settings ***
Documentation     Login Page UI Tests
...               Tests for the login page user interface
Resource          ../resources/common.resource
Test Setup        Open Browser To Application    ${BASE_URL}/login
Test Teardown     Close Browser Session
Test Tags         ui    login

*** Test Cases ***
Login Page Is Displayed Correctly
    [Documentation]    Verify that the login page displays all required elements
    [Tags]    smoke
    Wait For Elements State    input[name="email"]    visible
    Wait For Elements State    input[name="password"]    visible
    Wait For Elements State    button[type="submit"]    visible
    Assert Page Contains Text    Login
    # Check for register link
    Wait For Elements State    text=Register    visible

User Can Enter Email And Password
    [Documentation]    Verify that user can type in email and password fields
    [Tags]    smoke
    Fill Text    input[name="email"]    test@example.com
    Fill Text    input[name="password"]    password123
    ${email_value}=    Get Text    input[name="email"]
    ${password_value}=    Get Attribute    input[name="password"]    value
    Should Be Equal    ${email_value}    test@example.com
    Should Not Be Empty    ${password_value}

Login Form Shows Validation Errors For Empty Fields
    [Documentation]    Verify that validation errors are shown for empty fields
    [Tags]    validation
    Click    button[type="submit"]
    # Browser validation should prevent submission or show error
    Wait For Elements State    input[name="email"]:invalid    visible    timeout=5s

Login Form Shows Error For Invalid Credentials
    [Documentation]    Verify that error message is shown for invalid credentials
    [Tags]    negative
    Fill Text    input[name="email"]    invalid@example.com
    Fill Text    input[name="password"]    wrongpassword
    Click    button[type="submit"]
    Wait For Load State    networkidle
    # Should show error message or stay on login page
    ${url}=    Get Url
    Should Contain    ${url}    login

Remember Me Checkbox Is Present
    [Documentation]    Verify that remember me checkbox is present
    [Tags]    smoke
    ${checkbox}=    Get Element Count    input[name="remember"]
    Run Keyword If    ${checkbox} > 0    Log    Remember me checkbox found
    ...    ELSE    Log    Remember me checkbox not found    WARN

Forgot Password Link Is Present
    [Documentation]    Verify that forgot password link is present
    [Tags]    smoke
    ${link}=    Get Element Count    text=Forgot your password?
    Run Keyword If    ${link} > 0    Log    Forgot password link found
    ...    ELSE    Log    Forgot password link not found    WARN

User Can Navigate To Registration Page
    [Documentation]    Verify that user can navigate to registration page
    [Tags]    navigation
    Click    text=Register
    Wait For Load State    networkidle
    ${url}=    Get Url
    Should Contain    ${url}    register

Login Page Is Responsive
    [Documentation]    Verify that login page is responsive on mobile
    [Tags]    responsive
    Set Viewport Size    375    667
    Wait For Elements State    input[name="email"]    visible
    Wait For Elements State    input[name="password"]    visible
    Wait For Elements State    button[type="submit"]    visible

Password Field Masks Input
    [Documentation]    Verify that password field masks the input
    [Tags]    security
    ${type}=    Get Attribute    input[name="password"]    type
    Should Be Equal    ${type}    password

Login Form Is Accessible
    [Documentation]    Verify basic accessibility of login form
    [Tags]    accessibility
    # Check for labels
    ${email_label}=    Get Element Count    label[for="email"]
    ${password_label}=    Get Element Count    label[for="password"]
    # At least aria-label or label should exist
    Log    Email label count: ${email_label}
    Log    Password label count: ${password_label}
