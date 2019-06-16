<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Console\Commands;

use Illuminate\Console\Command;
use ReliqArts\GuidedImage\Contracts\ImageDispenser;

class DumpImageCache extends Command
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
    protected $description = 'Empty guided image file cache';

    /**
     * Execute the console command.
     *
     * @param ImageDispenser $imageDispenser
     */
    public function handle(ImageDispenser $imageDispenser): void
    {
        if ($imageDispenser->emptyCache()) {
            $this->line(PHP_EOL . '<info>✔</info> Success! Guided Image cache cleared.');
        } else {
            $this->line(PHP_EOL . '<info>✘</info> Couldn\'t clear Guided Image cache.');
        }
    }
}
