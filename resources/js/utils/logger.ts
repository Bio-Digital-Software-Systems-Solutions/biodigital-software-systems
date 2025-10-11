/**
 * Logger Utility
 *
 * Provides a centralized logging system with environment-aware behavior.
 * In production, logs are suppressed or sent to error tracking services.
 * In development, logs are displayed in the console with better formatting.
 */

type LogLevel = 'debug' | 'info' | 'warn' | 'error';

interface LoggerConfig {
    enabled: boolean;
    level: LogLevel;
    prefix?: string;
}

class Logger {
    private config: LoggerConfig;

    constructor(config: Partial<LoggerConfig> = {}) {
        const isDevelopment = import.meta.env.DEV;

        this.config = {
            enabled: isDevelopment,
            level: isDevelopment ? 'debug' : 'error',
            ...config,
        };
    }

    private shouldLog(level: LogLevel): boolean {
        if (!this.config.enabled) return false;

        const levels: LogLevel[] = ['debug', 'info', 'warn', 'error'];
        const currentLevelIndex = levels.indexOf(this.config.level);
        const messageLevelIndex = levels.indexOf(level);

        return messageLevelIndex >= currentLevelIndex;
    }

    private formatMessage(level: LogLevel, message: string, data?: unknown): string {
        const timestamp = new Date().toISOString();
        const prefix = this.config.prefix ? `[${this.config.prefix}]` : '';
        return `${timestamp} ${prefix} [${level.toUpperCase()}] ${message}`;
    }

    debug(message: string, data?: unknown): void {
        if (this.shouldLog('debug')) {
            console.debug(this.formatMessage('debug', message), data || '');
        }
    }

    info(message: string, data?: unknown): void {
        if (this.shouldLog('info')) {
            console.info(this.formatMessage('info', message), data || '');
        }
    }

    warn(message: string, data?: unknown): void {
        if (this.shouldLog('warn')) {
            console.warn(this.formatMessage('warn', message), data || '');
        }
    }

    error(message: string, error?: Error | unknown): void {
        if (this.shouldLog('error')) {
            console.error(this.formatMessage('error', message), error || '');

            // In production, send to error tracking service (Sentry, etc.)
            if (!import.meta.env.DEV && error instanceof Error) {
                // TODO: Send to Sentry or your error tracking service
                // Example: Sentry.captureException(error);
            }
        }
    }
}

// Export singleton instances for different contexts
export const logger = new Logger();
export const apiLogger = new Logger({ prefix: 'API' });
export const uiLogger = new Logger({ prefix: 'UI' });

// Export the Logger class for custom instances
export default Logger;
