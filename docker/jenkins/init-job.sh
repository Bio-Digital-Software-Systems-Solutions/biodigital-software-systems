#!/bin/bash
# Script to create the icc-munich-local pipeline job in Jenkins
# Usage: ./init-job.sh

JENKINS_URL="http://localhost:8081"
JENKINS_USER="admin"
JENKINS_PASS="admin123"
JOB_NAME="icc-munich-local"

echo "Waiting for Jenkins to be ready..."
until curl -s -u "$JENKINS_USER:$JENKINS_PASS" "$JENKINS_URL/api/json" > /dev/null 2>&1; do
    echo "  Jenkins not ready yet, waiting..."
    sleep 5
done
echo "Jenkins is ready!"

# Get CSRF crumb
CRUMB=$(curl -s -u "$JENKINS_USER:$JENKINS_PASS" "$JENKINS_URL/crumbIssuer/api/json" | grep -o '"crumb":"[^"]*"' | cut -d'"' -f4)

if [ -z "$CRUMB" ]; then
    echo "Could not get CSRF crumb. Trying without it..."
    CRUMB_HEADER=""
else
    echo "Got CSRF crumb"
    CRUMB_HEADER="-H Jenkins-Crumb:$CRUMB"
fi

# Check if job already exists
JOB_EXISTS=$(curl -s -o /dev/null -w "%{http_code}" -u "$JENKINS_USER:$JENKINS_PASS" "$JENKINS_URL/job/$JOB_NAME/api/json")

if [ "$JOB_EXISTS" == "200" ]; then
    echo "Job '$JOB_NAME' already exists. Updating..."
    curl -s -X POST -u "$JENKINS_USER:$JENKINS_PASS" \
        $CRUMB_HEADER \
        -H "Content-Type: application/xml" \
        --data-binary @/var/jenkins_home/jobs-config/icc-munich-local.xml \
        "$JENKINS_URL/job/$JOB_NAME/config.xml"
    echo "Job updated!"
else
    echo "Creating job '$JOB_NAME'..."
    curl -s -X POST -u "$JENKINS_USER:$JENKINS_PASS" \
        $CRUMB_HEADER \
        -H "Content-Type: application/xml" \
        --data-binary @/var/jenkins_home/jobs-config/icc-munich-local.xml \
        "$JENKINS_URL/createItem?name=$JOB_NAME"
    echo "Job created!"
fi

echo ""
echo "Access Jenkins at: $JENKINS_URL"
echo "Job URL: $JENKINS_URL/job/$JOB_NAME"
