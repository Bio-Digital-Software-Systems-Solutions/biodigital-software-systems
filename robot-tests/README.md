# Robot Framework Test Automation

This directory contains Robot Framework tests for automated testing of the ICC Munich application.

## Directory Structure

```
robot-tests/
├── api/                    # API tests
│   ├── authentication.robot
│   ├── events.robot
│   └── books.robot
├── ui/                     # UI/Browser tests
│   ├── login.robot
│   └── dashboard.robot
├── e2e/                    # End-to-end tests
│   ├── user_registration_flow.robot
│   └── event_participation_flow.robot
├── resources/              # Shared resources
│   ├── common.resource
│   └── api_keywords.resource
├── results/                # Test results (gitignored)
└── robot.yaml              # Configuration file
```

## Prerequisites

### Local Development
- Python 3.12+
- Robot Framework 7.x
- Docker and Docker Compose

### Docker (Recommended)
All dependencies are included in the Docker container.

## Running Tests

### Using Makefile (Recommended)

```bash
# Build the Robot Framework container
make robot-build

# Run all tests
make robot-test

# Run specific test suites
make robot-api          # API tests only
make robot-ui           # UI tests only
make robot-e2e          # E2E tests only
make robot-smoke        # Smoke tests only
make robot-critical     # Critical tests only

# Run tests with specific tag
make robot-tag TAG=authentication

# Run tests in debug mode
make robot-debug

# Run tests with automatic rerun on failure
make robot-rerun

# Clean test results
make robot-clean

# Generate metrics report
make robot-report

# Open shell in Robot container
make robot-shell
```

### Using Docker Compose Directly

```bash
# Build the container
docker-compose --profile testing build robot

# Run all tests
docker-compose --profile testing run --rm robot robot \
    --outputdir /robot/results \
    /robot/tests

# Run specific suite
docker-compose --profile testing run --rm robot robot \
    --outputdir /robot/results \
    --include api \
    /robot/tests/api
```

### Local Execution (Without Docker)

```bash
# Install dependencies
pip install -r docker/robot/requirements.txt
rfbrowser init chromium

# Run tests
robot --outputdir robot-results robot-tests/
```

## Test Tags

Tests are organized using tags for flexible execution:

| Tag | Description |
|-----|-------------|
| `api` | API/REST endpoint tests |
| `ui` | User interface tests |
| `e2e` | End-to-end flow tests |
| `smoke` | Quick validation tests |
| `critical` | Critical functionality tests |
| `authentication` | Authentication-related tests |
| `events` | Event management tests |
| `books` | Book/library tests |
| `negative` | Negative/error scenario tests |
| `validation` | Input validation tests |
| `security` | Security-related tests |

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `BASE_URL` | Application base URL | `http://nginx` |
| `API_URL` | API endpoint URL | `http://nginx/api` |
| `DB_HOST` | Database host | `mysql` |
| `DB_PORT` | Database port | `3306` |
| `DB_NAME` | Database name | `icc_munich` |
| `DB_USER` | Database user | `icc_user` |
| `DB_PASSWORD` | Database password | `secret` |
| `BROWSER` | Browser for UI tests | `chromium` |
| `HEADLESS` | Run browser headless | `true` |

## Test Results

After running tests, results are available in `robot-results/`:

- `report.html` - High-level test report
- `log.html` - Detailed execution log
- `output.xml` - Machine-readable results
- `*.png` - Screenshots (on failure)

## CI/CD Integration

Robot Framework tests are integrated into the CI/CD pipeline:

1. **PR Checks**: Smoke tests run on every pull request
2. **Main Branch**: Full API test suite runs on merge to main
3. **Separate Workflow**: Complete test suite available via `robot-tests.yml`

### Running in CI

The workflow can be triggered manually with specific test types:
- `all` - Run all tests
- `api` - API tests only
- `ui` - UI tests only
- `e2e` - E2E tests only
- `smoke` - Smoke tests only
- `critical` - Critical tests only

## Writing New Tests

### API Test Example

```robot
*** Settings ***
Documentation     Example API test
Resource          ../resources/common.resource
Resource          ../resources/api_keywords.resource
Suite Setup       Create API Session
Test Tags         api

*** Test Cases ***
Example API Test
    [Documentation]    Description of the test
    [Tags]    smoke
    ${response}=    GET On Session    api    /endpoint
    Assert Response Status    ${response}    200
```

### UI Test Example

```robot
*** Settings ***
Documentation     Example UI test
Resource          ../resources/common.resource
Test Setup        Open Browser To Application
Test Teardown     Close Browser Session
Test Tags         ui

*** Test Cases ***
Example UI Test
    [Documentation]    Description of the test
    [Tags]    smoke
    Assert Page Contains Text    Expected Text
    Click    button[type="submit"]
    Wait For Load State    networkidle
```

## Troubleshooting

### Common Issues

1. **Connection refused to application**
   - Ensure the application is running (`make start`)
   - Check that nginx container is healthy

2. **Database connection errors**
   - Verify MySQL container is running
   - Check database credentials in environment variables

3. **Browser tests failing**
   - Ensure Playwright browsers are initialized
   - Check if running in headless mode for CI

4. **Permission errors**
   - Check file permissions on results directory
   - Ensure Docker volume mounts are correct

### Debug Mode

Run tests in debug mode for detailed output:

```bash
make robot-debug
```

This creates a `debug.log` file with detailed execution information.

## Contributing

1. Follow the existing test structure
2. Use appropriate tags for new tests
3. Add documentation to test cases
4. Run smoke tests before committing
5. Update this README if adding new features
