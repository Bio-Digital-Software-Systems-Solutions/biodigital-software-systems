# Dockerfile for Robot Framework Test Automation
# Supports API testing, Browser testing (Playwright), and E2E tests

FROM python:3.12-slim

LABEL maintainer="ICC Munich Dev Team"
LABEL description="Robot Framework test automation container"

# Set environment variables
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1 \
    ROBOT_REPORTS_DIR=/robot/results \
    ROBOT_TESTS_DIR=/robot/tests

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    wget \
    gnupg \
    ca-certificates \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libatspi2.0-0 \
    libcups2 \
    libdbus-1-3 \
    libdrm2 \
    libgbm1 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libwayland-client0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxkbcommon0 \
    libxrandr2 \
    xdg-utils \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js (required for Playwright/Browser library)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Create working directory
WORKDIR /robot

# Copy requirements file
COPY docker/robot/requirements.txt /robot/requirements.txt

# Install Python packages
RUN pip install --upgrade pip && \
    pip install -r requirements.txt

# Install Playwright browsers
RUN rfbrowser init chromium

# Create directories for tests and results
RUN mkdir -p ${ROBOT_TESTS_DIR} ${ROBOT_REPORTS_DIR}

# Copy test files (will be overridden by volume mount in development)
COPY robot-tests/ ${ROBOT_TESTS_DIR}/

# Set default command
CMD ["robot", "--outputdir", "/robot/results", "/robot/tests"]
