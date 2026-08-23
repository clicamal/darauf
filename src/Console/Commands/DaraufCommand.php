<?php

declare(strict_types=1);

namespace Clicamal\Darauf\Console\Commands;

use Illuminate\Console\Command;

class DaraufCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'darauf:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package darauf.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Darauf placeholder command executed.');

        return self::SUCCESS;
    }
}
