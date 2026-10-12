<?php
/**
 * Bug Tracking System - Database Connection Manager (PDO)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    // Database configuration settings (Default XAMPP setup)
    private static string $host = '127.0.0.1';
    private static string $dbname = 'bug_tracking_system';
    private static string $username = 'root';
    private static string $password = '';
    private static int $port = 3306;
    private static string $charset = 'utf8mb4';

    /**
     * Get singleton PDO database connection instance
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$dbname . ";charset=" . self::$charset;
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::$charset
            ];

            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // If database does not exist yet or MySQL is not running, provide helpful diagnostic
                die(self::renderDbError($e->getMessage()));
            }
        }

        return self::$instance;
    }

    /**
     * Render a clean, helpful error screen if database connection fails
     */
    private static function renderDbError(string $errorMessage): string {
        $dbName = self::$dbname;
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Error - BugTrace</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    background-color: #0f172a;
                    color: #f8fafc;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .error-box {
                    background: #1e293b;
                    border: 1px solid #334155;
                    border-radius: 12px;
                    max-width: 600px;
                    width: 100%;
                    padding: 32px;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
                }
                h1 {
                    color: #f43f5e;
                    font-size: 1.5rem;
                    margin-top: 0;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                p { color: #94a3b8; line-height: 1.6; }
                .code-block {
                    background: #090d16;
                    border-radius: 8px;
                    padding: 14px;
                    font-family: monospace;
                    font-size: 0.88rem;
                    color: #fb7185;
                    margin: 16px 0;
                    overflow-x: auto;
                }
                .steps {
                    background: rgba(37, 99, 235, 0.1);
                    border-left: 4px solid #2563eb;
                    padding: 14px 18px;
                    border-radius: 0 8px 8px 0;
                    margin-top: 20px;
                }
                .steps ol { margin: 8px 0 0 18px; padding: 0; color: #cbd5e1; }
                .steps li { margin-bottom: 6px; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    Database Connection Required
                </h1>
                <p>The application could not connect to MySQL. Please verify that MySQL is running in your <strong>XAMPP Control Panel</strong> and that the database has been imported.</p>
                <div class="code-block">$errorMessage</div>
                <div class="steps">
                    <strong style="color: #60a5fa;">Quick Setup Instructions:</strong>
                    <ol>
                        <li>Open <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> next to MySQL.</li>
                        <li>Open <a href="http://localhost/phpmyadmin" target="_blank" style="color: #38bdf8;">phpMyAdmin</a> in your browser.</li>
                        <li>Create a database named <code>$dbName</code>.</li>
                        <li>Import the file <code>database/bug_tracking_system.sql</code>.</li>
                        <li>Refresh this page.</li>
                    </ol>
                </div>
            </div>
        </body>
        </html>
HTML;
    }
}

/**
 * Shorthand helper function to get database instance
 */
function get_db(): PDO {
    return Database::getConnection();
}
