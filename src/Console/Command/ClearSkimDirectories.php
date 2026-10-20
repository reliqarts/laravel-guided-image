<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Console\Command;

use Illuminate\Console\Command;
use ReliqArts\GuidedImage\Contract\ImageDispenser;

final class ClearSkimDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guidedimage:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear guided image skim directories (file cache)';

    /**
     * Execute the console command.
     */
    public function handle(ImageDispenser $imageDispenser): int
    {
        if ($imageDispenser->emptyCache()) {
            $this->line(PHP_EOL.'<info>✔</info> Success! Guided Image cache cleared.');

            return Command::SUCCESS;
        }

        $this->line(PHP_EOL.'<info>✘</info> Couldn\'t clear Guided Image cache.');

        return Command::FAILURE;
    }
}
