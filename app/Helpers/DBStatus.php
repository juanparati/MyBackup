<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use PDO;

class DBStatus
{
    /**
     * PDO connection.
     */
    protected PDO|\Closure $pdo;

    /**
     * Singleton instance storage.
     */
    protected static ?self $_instance = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->pdo = DB::connection('target')->getPdo();
    }

    /**
     * Singleton method.
     */
    public static function make(): static
    {
        static::$_instance = static::$_instance ?: new self();

        return static::$_instance;
    }

    /**
     * Check if is successfully connected.
     */
    public function isConnected(): bool
    {
        try {
            return (bool) $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Find if the slave server has an active replication.
     */
    public function hasActiveReplication(): bool
    {
        if (! $this->isConnected()) {
            return false;
        }

        $slaveStatus = collect(
            $this->pdo->query('SHOW SLAVE STATUS')->fetchAll()
        )->mapWithKeys(fn ($r) => [$r[0] => ctype_digit($r[1]) ? ((int) $r[1]) : $r[1]]);

        if ($slaveStatus->isEmpty()) {
            return false;
        }

        if (empty($slaveStatus['Slave_IO_Running']) || empty($slaveStatus['Slave_SQL_Running'])) {
            return false;
        }

        return true;
    }
}
