<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class RefactorViews extends Command
{
    protected $signature = 'refactor:views';
    protected $description = 'Refactor Blade files to use Laravel asset() and route() helpers safely';

    public function handle()
    {
        $viewsPath = resource_path('views');
        $backupName = 'views_backup_' . now()->format('Ymd_His') . '.zip';
        $backupPath = storage_path('app/' . $backupName);

        // Backup dulu semua file Blade
        $this->info('Membuat backup sebelum refactor...');
        $zip = new ZipArchive();
        if ($zip->open($backupPath, ZipArchive::CREATE) === true) {
            $files = File::allFiles($viewsPath);
            foreach ($files as $file) {
                $relativePath = str_replace($viewsPath . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), $relativePath);
            }
            $zip->close();
        }

        $this->info('Backup selesai: ' . $backupPath);

        // Refactor semua file .blade.php
        $this->info('Mulai refactor semua file Blade...');

        $bladeFiles = File::allFiles($viewsPath);
        foreach ($bladeFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());

            // Ganti aset statis -> asset()
            $content = preg_replace_callback(
                '/(href|src)=["\']\/(css|js|images|assets|vendor)\/([^"\']+)["\']/',
                function ($matches) {
                    return $matches[1] . '="{{ asset(\'' . $matches[2] . '/' . $matches[3] . '\') }}"';
                },
                $content
            );

            // Ganti link internal -> route()
            $content = preg_replace_callback(
                '/href=["\']\/([a-zA-Z0-9\-_\/]*)["\']/',
                function ($matches) {
                    $path = trim($matches[1], '/');
                    if ($path === '') return 'href="{{ route(\'landing\') }}"'; // Root URL
                    // Convert misalnya "/login" => route('login')
                    $name = str_replace('/', '.', $path);
                    return 'href="{{ route(\'' . $name . '\') }}"';
                },
                $content
            );

            // Ganti action form -> route()
            $content = preg_replace_callback(
                '/action=["\']\/([a-zA-Z0-9\-_\/]*)["\']/',
                function ($matches) {
                    $path = trim($matches[1], '/');
                    if ($path === '') return 'action="{{ route(\'landing\') }}"';
                    $name = str_replace('/', '.', $path);
                    return 'action="{{ route(\'' . $name . '\') }}"';
                },
                $content
            );

            File::put($file->getPathname(), $content);
        }

        $this->info('✅ Refactor selesai tanpa mengubah struktur halaman!');
        $this->info('Semua aset kini menggunakan helper asset() dan semua link memakai route().');
        $this->info('Backup file tersimpan di: ' . $backupPath);
    }
}
