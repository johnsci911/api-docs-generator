<?php

namespace App\Console\Commands;

use App\Services\DocumentationService;
use Illuminate\Console\Command;

class GenerateDocumentation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:generate
                            {--force : Force regeneration of all documentation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate API documentation automatically';

    protected DocumentationService $documentationService;

    public function __construct(DocumentationService $documentationService)
    {
        parent::__construct();
        $this->documentationService = $documentationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Generating API documentation...');
        $this->newLine();

        try {
            $stats = $this->documentationService->generateDocumentation();

            $this->info('✅ Documentation generated successfully!');
            $this->newLine();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Routes', $stats['total']],
                    ['Created/Updated', $stats['created']],
                    ['Deleted (Stale)', $stats['deleted'] ?? 0],
                    ['Errors', $stats['errors']],
                ]
            );

            if ($stats['errors'] > 0) {
                $this->warn("⚠️  {$stats['errors']} route(s) had errors. Check logs for details.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error generating documentation: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
