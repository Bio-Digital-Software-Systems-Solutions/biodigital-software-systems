// ICC Munich CI/CD Pipeline
// This Jenkinsfile defines the complete CI/CD pipeline for the Laravel + React application

pipeline {
    agent any

    environment {
        // Application settings
        APP_NAME = 'icc-munich'
        APP_ENV = 'testing'

        // Docker settings
        DOCKER_BUILDKIT = '1'
        COMPOSE_DOCKER_CLI_BUILD = '1'

        // Node.js settings
        NODE_OPTIONS = '--max-old-space-size=4096'

        // PHP settings
        COMPOSER_NO_INTERACTION = '1'
        COMPOSER_ALLOW_SUPERUSER = '1'
    }

    options {
        // Build options
        timeout(time: 30, unit: 'MINUTES')
        timestamps()
        ansiColor('xterm')
        buildDiscarder(logRotator(numToKeepStr: '10', daysToKeepStr: '30'))
        disableConcurrentBuilds()

        // Skip default checkout to control it manually
        skipDefaultCheckout(true)
    }

    stages {
        // ==========================================
        // Stage 1: Checkout
        // ==========================================
        stage('Checkout') {
            steps {
                cleanWs()
                checkout scm

                script {
                    // Get git info for later use
                    env.GIT_COMMIT_SHORT = sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()
                    env.GIT_BRANCH_NAME = sh(script: 'git rev-parse --abbrev-ref HEAD', returnStdout: true).trim()
                    env.GIT_COMMIT_MSG = sh(script: 'git log -1 --pretty=%B', returnStdout: true).trim()
                }

                echo "Building branch: ${env.GIT_BRANCH_NAME}"
                echo "Commit: ${env.GIT_COMMIT_SHORT}"
                echo "Message: ${env.GIT_COMMIT_MSG}"
            }
        }

        // ==========================================
        // Stage 2: Prepare Environment
        // ==========================================
        stage('Prepare Environment') {
            steps {
                script {
                    // Create .env file for testing
                    sh '''
                        cp .env.example .env.testing || cp .env.example .env
                        echo "APP_ENV=testing" >> .env.testing
                        echo "APP_DEBUG=true" >> .env.testing
                        echo "DB_CONNECTION=sqlite" >> .env.testing
                        echo "DB_DATABASE=:memory:" >> .env.testing
                        echo "CACHE_DRIVER=array" >> .env.testing
                        echo "SESSION_DRIVER=array" >> .env.testing
                        echo "QUEUE_CONNECTION=sync" >> .env.testing
                    '''
                }
            }
        }

        // ==========================================
        // Stage 3: Install Dependencies (Parallel)
        // ==========================================
        stage('Install Dependencies') {
            parallel {
                stage('PHP Dependencies') {
                    steps {
                        sh '''
                            composer install --no-progress --prefer-dist --optimize-autoloader
                            php artisan key:generate --env=testing || true
                        '''
                    }
                }

                stage('Node Dependencies') {
                    steps {
                        sh '''
                            npm ci --legacy-peer-deps
                        '''
                    }
                }
            }
        }

        // ==========================================
        // Stage 4: Code Quality (Parallel)
        // ==========================================
        stage('Code Quality') {
            parallel {
                stage('PHP CodeSniffer') {
                    steps {
                        sh 'vendor/bin/phpcs --report=checkstyle --report-file=phpcs-report.xml || true'
                    }
                    post {
                        always {
                            recordIssues(
                                enabledForFailure: true,
                                tool: checkStyle(pattern: 'phpcs-report.xml', reportEncoding: 'UTF-8')
                            )
                        }
                    }
                }

                stage('PHPStan') {
                    steps {
                        sh 'vendor/bin/phpstan analyse --memory-limit=2G --error-format=checkstyle > phpstan-report.xml || true'
                    }
                    post {
                        always {
                            recordIssues(
                                enabledForFailure: true,
                                tool: checkStyle(pattern: 'phpstan-report.xml', reportEncoding: 'UTF-8', name: 'PHPStan')
                            )
                        }
                    }
                }

                stage('Laravel Pint') {
                    steps {
                        sh 'vendor/bin/pint --test || true'
                    }
                }

                stage('ESLint') {
                    steps {
                        sh 'npm run lint -- --format json --output-file eslint-report.json || true'
                    }
                    post {
                        always {
                            recordIssues(
                                enabledForFailure: true,
                                tool: esLint(pattern: 'eslint-report.json')
                            )
                        }
                    }
                }

                stage('TypeScript Check') {
                    steps {
                        sh 'npx tsc --noEmit || true'
                    }
                }
            }
        }

        // ==========================================
        // Stage 5: Build Frontend
        // ==========================================
        stage('Build Frontend') {
            steps {
                sh '''
                    npm run build
                '''
            }
        }

        // ==========================================
        // Stage 6: Tests (Parallel)
        // ==========================================
        stage('Tests') {
            parallel {
                stage('PHP Unit Tests') {
                    steps {
                        sh '''
                            php artisan test --env=testing --parallel --coverage-clover=coverage.xml --log-junit=junit-report.xml || true
                        '''
                    }
                    post {
                        always {
                            junit allowEmptyResults: true, testResults: 'junit-report.xml'
                            publishCoverage adapters: [
                                coberturaAdapter(path: 'coverage.xml')
                            ], sourceFileResolver: sourceFiles('NEVER_STORE')
                        }
                    }
                }

                stage('Frontend Tests') {
                    steps {
                        sh '''
                            npm test -- --run --coverage --reporter=junit --outputFile=jest-report.xml || true
                        '''
                    }
                    post {
                        always {
                            junit allowEmptyResults: true, testResults: 'jest-report.xml'
                        }
                    }
                }

                stage('Pest Tests') {
                    steps {
                        sh '''
                            vendor/bin/pest --ci --coverage-clover=pest-coverage.xml || true
                        '''
                    }
                }
            }
        }

        // ==========================================
        // Stage 7: Security Scan
        // ==========================================
        stage('Security Scan') {
            parallel {
                stage('PHP Security Check') {
                    steps {
                        sh '''
                            composer audit --format=json > composer-audit.json || true
                        '''
                    }
                }

                stage('NPM Audit') {
                    steps {
                        sh '''
                            npm audit --json > npm-audit.json || true
                        '''
                    }
                }
            }
        }

        // ==========================================
        // Stage 8: Build Docker Image
        // ==========================================
        stage('Build Docker Image') {
            when {
                anyOf {
                    branch 'main'
                    branch 'develop'
                    branch 'release/*'
                }
            }
            steps {
                script {
                    def imageTag = "${env.APP_NAME}:${env.GIT_COMMIT_SHORT}"
                    def latestTag = "${env.APP_NAME}:latest"

                    sh """
                        docker build \
                            --target production \
                            --tag ${imageTag} \
                            --tag ${latestTag} \
                            --build-arg BUILD_DATE=\$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
                            --build-arg VCS_REF=${env.GIT_COMMIT_SHORT} \
                            .
                    """

                    env.DOCKER_IMAGE = imageTag
                }
            }
        }

        // ==========================================
        // Stage 9: Integration Tests (Docker)
        // ==========================================
        stage('Integration Tests') {
            when {
                anyOf {
                    branch 'main'
                    branch 'develop'
                }
            }
            steps {
                script {
                    sh '''
                        # Start test environment
                        docker-compose -f docker-compose.yml up -d mysql redis

                        # Wait for MySQL to be ready
                        sleep 30

                        # Run integration tests
                        docker-compose -f docker-compose.yml run --rm app php artisan test --testsuite=Feature || true

                        # Cleanup
                        docker-compose -f docker-compose.yml down -v
                    '''
                }
            }
        }

        // ==========================================
        // Stage 10: Robot Framework Tests
        // ==========================================
        stage('Robot Framework Tests') {
            when {
                anyOf {
                    branch 'main'
                    branch 'develop'
                }
            }
            steps {
                script {
                    sh '''
                        # Start full application stack
                        docker-compose -f docker-compose.yml up -d

                        # Wait for services
                        sleep 60

                        # Run Robot Framework smoke tests
                        docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include smoke --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests" || true

                        # Cleanup
                        docker-compose -f docker-compose.yml down -v
                    '''
                }
            }
            post {
                always {
                    robot(
                        outputPath: 'robot-results',
                        outputFileName: 'output.xml',
                        reportFileName: 'report.html',
                        logFileName: 'log.html',
                        passThreshold: 80.0,
                        unstableThreshold: 60.0
                    )
                }
            }
        }

        // ==========================================
        // Stage 11: Deploy to Staging
        // ==========================================
        stage('Deploy to Staging') {
            when {
                branch 'develop'
            }
            steps {
                echo 'Deploying to Staging environment...'
                script {
                    // Add your staging deployment commands here
                    sh '''
                        echo "Staging deployment would happen here"
                        # Example: docker-compose -f docker-compose.staging.yml up -d
                    '''
                }
            }
        }

        // ==========================================
        // Stage 12: Deploy to Production
        // ==========================================
        stage('Deploy to Production') {
            when {
                branch 'main'
            }
            input {
                message "Deploy to Production?"
                ok "Yes, deploy it!"
                parameters {
                    choice(
                        name: 'DEPLOY_TYPE',
                        choices: ['rolling', 'blue-green'],
                        description: 'Select deployment strategy'
                    )
                }
            }
            steps {
                echo 'Deploying to Production environment...'
                script {
                    // Add your production deployment commands here
                    sh """
                        echo "Production deployment would happen here"
                        echo "Deployment type: ${DEPLOY_TYPE}"
                        # Example: docker-compose -f docker-compose.prod.yml up -d
                    """
                }
            }
        }
    }

    // ==========================================
    // Post-build Actions
    // ==========================================
    post {
        always {
            // Archive artifacts
            archiveArtifacts artifacts: '**/coverage.xml, **/junit-report.xml, **/phpcs-report.xml, **/phpstan-report.xml', allowEmptyArchive: true

            // Cleanup workspace
            cleanWs(
                cleanWhenNotBuilt: false,
                deleteDirs: true,
                disableDeferredWipeout: true,
                notFailBuild: true,
                patterns: [
                    [pattern: '.git', type: 'EXCLUDE'],
                    [pattern: 'vendor/**', type: 'EXCLUDE'],
                    [pattern: 'node_modules/**', type: 'EXCLUDE']
                ]
            )
        }

        success {
            echo "✅ Build #${env.BUILD_NUMBER} succeeded!"

            // Send success notification (uncomment when Slack is configured)
            // slackSend(
            //     channel: '#ci-cd',
            //     color: 'good',
            //     message: "✅ Build #${env.BUILD_NUMBER} succeeded for ${env.GIT_BRANCH_NAME}\nCommit: ${env.GIT_COMMIT_SHORT}\n${env.BUILD_URL}"
            // )
        }

        failure {
            echo "❌ Build #${env.BUILD_NUMBER} failed!"

            // Send failure notification (uncomment when Slack is configured)
            // slackSend(
            //     channel: '#ci-cd',
            //     color: 'danger',
            //     message: "❌ Build #${env.BUILD_NUMBER} FAILED for ${env.GIT_BRANCH_NAME}\nCommit: ${env.GIT_COMMIT_SHORT}\n${env.BUILD_URL}"
            // )
        }

        unstable {
            echo "⚠️ Build #${env.BUILD_NUMBER} is unstable!"
        }
    }
}
