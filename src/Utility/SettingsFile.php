<?php
/**
 * Finna statistics utility scripts.
 * Copyright (c) 2015 University Of Helsinki (The National Library Of Finland)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Riikka Kalliomäki <riikka.kalliomaki@helsinki.fi>
 * @copyright 2015 University Of Helsinki (The National Library Of Finland)
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPL-3.0
 */

namespace Finna\Stats\Utility;

/**
 * Utility for reading settings from JSON file.
 */
class SettingsFile implements \ArrayAccess
{
    /** @var string Path to the settings file */
    private $filename;

    /** @var array Settings read from the settings file */
    private $settings;

    /**
     * Creates a new instance of SettingsFile and reads the settings from the file.
     * @param string $file Path to the settings file
     */
    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException("The settings file '$file' does not exist'");
        }

        if (!is_readable($file)) {
            throw new \RuntimeException("Cannot read the settings file '$file'");
        }

        $settings = json_decode(file_get_contents($file), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Error parsing settings: " . json_last_error_msg());
        }

        $this->filename = $file;
        $this->settings = $settings;
    }

    /**
     * Creates a PDO instance using settings from the file
     * @return \PDO Database connection created from the settings
     */
    public function loadDatabase()
    {
        $pdo = new \PDO(
            sprintf("mysql:dbname=%s;host=%s;charset=utf8", $this['database'], $this['hostname']),
            $this['username'],
            $this['password'],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . date('P') . "'",
            ]
        );

        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    }

    /**
     * Tells if the settings value exists
     * @param string $offset Name of the settings value
     * @return bool True if setting exists, false if not
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->settings);
    }

    /**
     * Returns the value for the setting.
     * @param string $offset Name of the settings value
     * @return mixed Value for the setting
     */
    public function offsetGet($offset)
    {
        if (!isset($this[$offset])) {
            throw new \InvalidArgumentException("Invalid setting name '$offset'");
        }
        return $this->settings[$offset];
    }

    /**
     * Sets the settings value.
     * @param string $offset Name of the settings value
     * @param mixed $value Value for the setting
     */
    public function offsetSet($offset, $value)
    {
        $this->settings[$offset] = $value;
    }

    /**
     * Unset the settings value.
     * @param string $offset Name of the settings value
     */
    public function offsetUnset($offset)
    {
        unset($this->settings[$offset]);
    }
}
