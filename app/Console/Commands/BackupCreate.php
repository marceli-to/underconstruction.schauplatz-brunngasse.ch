<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;

class BackupCreate extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'backup:create';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Creates a full backup of the content and assets folders.';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Starting backup...');
    
    // Get the current date and time.
    $now = \Carbon\Carbon::now()->format('Y-m-d_H-i-s');

    // Create a new zip archive.
    $zip = new \ZipArchive();
    $zip->open(storage_path('app/backup_' . $now . '.zip'), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    // Add the content folder to the archive.
    $contentFolder = \base_path('content');
    $contentFolderIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($contentFolder));
    foreach ($contentFolderIterator as $file) {
      if ($file->isDir()) {
        continue;
      }
      $filePath = $file->getRealPath();
      $relativePath = 'content' . DIRECTORY_SEPARATOR . substr($filePath, strlen($contentFolder) + 1);
      $zip->addFile($filePath, $relativePath);
    }

    // Add the assets folder to the archive.
    $assetsFolder = \base_path('public/assets');
    $assetsFolderIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($assetsFolder));
    foreach ($assetsFolderIterator as $file) {
      if ($file->isDir()) {
        continue;
      }
      $filePath = $file->getRealPath();
      $relativePath = 'public/assets' . DIRECTORY_SEPARATOR . substr($filePath, strlen($assetsFolder) + 1);
      $zip->addFile($filePath, $relativePath);
    }

    // Close the archive.
    $zip->close();

    $this->info('Backup finished!');
  }
}
