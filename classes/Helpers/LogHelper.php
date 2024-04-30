<?php
/**
 * @file classes/Helpers/LogHelper.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LogHelper
 * @brief Provides static helper methods for logging messages.
 */

namespace APP\plugins\generic\citationManager\classes\Helpers;

use Config;

class LogHelper
{
    /**
     * Regex for capturing sensitive data, e.g. password, token.
     * These regexes are meant for json syntax.
     *
     * @var array|string[]
     */
    public static array $regexes = [
        '/(?<=password":")(.*?)(?=\")/',
        '/(?<=token":")(.*?)(?=\")/',
        '/(?<=token )(.*?)(?=\")/'
    ];

    /**
     * Log an informational message.
     *
     * @param mixed $message The message to be logged.
     * @return bool Returns true on success, false on failure.
     */
    public static function logInfo(mixed $message): bool
    {
        $level = 'INF';
        $message = self::processMessage($message, $level);
        return self::writeToFile($message, 'INF');
    }

    /**
     * Log an error message.
     *
     * @param mixed $message The error message to be logged.
     * @return bool Returns true on success, false on failure.
     */
    public static function logError(mixed $message): bool
    {
        $level = 'ERR';
        $message = self::processMessage($message, $level);
        return self::writeToFile($message, $level);
    }

    /**
     * Log an debug message.
     *
     * @param mixed $message The error message to be logged.
     * @return bool Returns true on success, false on failure.
     */
    public static function logDebug(mixed $message): bool
    {
        $level = 'DBG';
        $message = self::processMessage($message, $level);
        return self::writeToFile($message, $level);
    }

    /**
     * Change object / array to string, normalize whitespace
     *
     * @param mixed $message
     * @param string $level
     * @return string
     */
    private static function processMessage(mixed $message, string $level): string
    {
        if (empty($message)) $message = '-empty-';

        // convert message to string
        if (is_object($message) || is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) $message = var_export($message, true);
        }

        // line endings, whitespace
        $message = str_replace(array("\r", "\n"), ' ', $message);
        $message = preg_replace('!\s+!', ' ', $message);

        // get debug backtrace
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $message = date('Y-m-d H:i:s') . substr(microtime(), 1, 4) . ' ' . $level . ' ' .
            $backTrace[2]['class'] . '\\' . $backTrace[2]['function'] . ': ' . $message . "\n";

        // add extra debug information if debug level
        if ($level === 'DBG') {
            $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $message .= '                            ' . json_encode($backTrace, JSON_UNESCAPED_SLASHES) . "\n";
        }

        // remove sensitive information such as passwords
        // do this after converting to json
        foreach (self::$regexes as $index => $regex) {
            $matches = [];
            preg_match($regex, $message, $matches);
            if (!empty($matches[0]))
                $message = str_replace($matches[0], '*****', $message);
        }

        return $message;
    }

    /**
     * Write message to a log file.
     *
     * @param mixed $message The message to be logged.
     * @param string $level The log level.
     * @return bool Returns true on success, false on failure.
     */
    private static function writeToFile(mixed $message, string $level): bool
    {
        $path = Config::getVar('files', 'files_dir');
        $file = strtolower(str_replace(['\\classes\\Helpers', '\\'], ['', '_'], __NAMESPACE__ . '.log'));
        $filePath = $path . DIRECTORY_SEPARATOR . $file;

        return error_log($message, 3, $filePath);
    }
}
