<?php

namespace App\Filters;

use App\Services\SchemaUpdateService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * Switch default DB connection to the demo group for demo sessions.
 */
class DemoDbFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('is_demo')) {
            $config = config(Database::class);
            $config->defaultGroup = 'demo';

            // Shared connection for Models that call Database::connect() with null group.
            Database::connect();

            $demoDb = Database::connect('demo', false);
            $schema = new SchemaUpdateService($demoDb);
            $schema->ensureQuoteConfirmationsTable();
            $schema->ensureCreditorsAndIncomingInvoices();
            $schema->ensureDemoFantasyAccountingData();

            return;
        }

        $schema = new SchemaUpdateService();
        $schema->ensureQuoteConfirmationsTable();
        $schema->ensureCreditorsAndIncomingInvoices();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
