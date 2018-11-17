<?php

namespace ReliQArts\GuidedImage\Console\Commands;

use File;
use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command;

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
     * Guided image cache directory.
     */
    protected $skimDir;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(Config $config): void
    {
        $this->skimDir = storage_path($config->get('guidedimage.storage.skim_dir'));

        // remove skim dir
        if (File::isDirectory($this->skimDir)) {
            File::deleteDirectory($this->skimDir, true);
            $this->line(PHP_EOL . '<info>✔</info> Success! Guided image cache cleared.');
        } else {
            $this->line(PHP_EOL . '<info>✔</info> It wasn\'t there! Guided image cache directory does not exist.');
        }
    }
}
