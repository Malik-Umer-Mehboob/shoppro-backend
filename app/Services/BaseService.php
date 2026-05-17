<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    /**
     * Handle business logic with transaction safety.
     * 
     * @param callable $callback
     * @param string $errorLogMessage
     * @return mixed
     * @throws Exception
     */
    protected function handleTransaction(callable $callback, string $errorLogMessage = 'Service transaction failed')
    {
        DB::beginTransaction();
        try {
            $result = $callback();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($errorLogMessage . ': ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
