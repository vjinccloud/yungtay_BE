<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:orphaned-records
                           {--dry-run : Show what would be cleaned without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned records from view logs, statistics, demographics, user collections and images with missing files';

    /**
     * Content type to table mapping for view records
     *
     * @var array
     */
    protected array $viewContentTables = [
        'article' => 'articles',
        'drama' => 'dramas',
        'program' => 'programs',
        'live' => 'lives',
        'radio' => 'radios',
    ];

    /**
     * Content type to table mapping for user collections
     *
     * @var array
     */
    protected array $collectionContentTables = [
        'articles' => 'articles',
        'drama' => 'dramas',
        'program' => 'programs',
        'live' => 'lives',
        'radio' => 'radios',
    ];

    /**
     * Attachable type to table mapping for operation logs
     *
     * @var array
     */
    protected array $operationLogTables = [
        'App\\Models\\Article' => 'articles',
        'App\\Models\\Drama' => 'dramas',
        'App\\Models\\Program' => 'programs',
        'App\\Models\\Live' => 'lives',
        'App\\Models\\Radio' => 'radios',
        'App\\Models\\Banner' => 'banners',
        'App\\Models\\DramaEpisode' => 'drama_episodes',
        'App\\Models\\ProgramEpisode' => 'program_episodes',
        'App\\Models\\DramaTheme' => 'drama_themes',
        'App\\Models\\ProgramTheme' => 'program_themes',
        'App\\Models\\Category' => 'categories',
        'App\\Models\\News' => 'news',
        'App\\Models\\AdminUser' => 'admin_users',
        'App\\Models\\User' => 'users',
    ];

    /**
     * Attachable type to table mapping for images
     *
     * @var array
     */
    protected array $imageTables = [
        'App\\Models\\Article' => 'articles',
        'App\\Models\\Drama' => 'dramas',
        'App\\Models\\Program' => 'programs',
        'App\\Models\\Live' => 'lives',
        'App\\Models\\Radio' => 'radios',
        'App\\Models\\Banner' => 'banners',
        'App\\Models\\DramaEpisode' => 'drama_episodes',
        'App\\Models\\ProgramEpisode' => 'program_episodes',
        'App\\Models\\News' => 'news',
        'App\\Models\\AdminUser' => 'admin_users',
        'App\\Models\\User' => 'users',
        'App\\Models\\WebsiteInfo' => 'website_infos',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 Dry Run Mode - No records will be actually deleted');
        } else {
            $this->info('🧹 Starting cleanup of orphaned records...');
        }

        $totalCleaned = 0;

        DB::transaction(function () use ($isDryRun, &$totalCleaned) {
            // Clean view_logs table
            $totalCleaned += $this->cleanupTable('view_logs', $this->viewContentTables, $isDryRun);
            
            // Clean view_statistics table
            $totalCleaned += $this->cleanupTable('view_statistics', $this->viewContentTables, $isDryRun);
            
            // Clean view_demographics table
            $totalCleaned += $this->cleanupTable('view_demographics', $this->viewContentTables, $isDryRun);
            
            // Clean user_collections table
            $totalCleaned += $this->cleanupTable('user_collections', $this->collectionContentTables, $isDryRun);
            
            // Clean operation_logs table
            $totalCleaned += $this->cleanupOperationLogs($isDryRun);
            
            // Clean images table (orphaned records)
            $totalCleaned += $this->cleanupImages($isDryRun);
            
            // Clean missing image files
            //$totalCleaned += $this->cleanupMissingImageFiles($isDryRun);
        });

        if ($isDryRun) {
            $this->info("✅ Dry run completed. Total records that would be cleaned: {$totalCleaned}");
        } else {
            $this->info("✅ Cleanup completed. Total records cleaned: {$totalCleaned}");
            Log::info("Orphaned records cleanup completed", [
                'total_cleaned' => $totalCleaned,
                'timestamp' => now()
            ]);
        }

        return 0;
    }

    /**
     * Clean orphaned records from a specific table
     *
     * @param string $tableName
     * @param array $contentTables
     * @param bool $isDryRun
     * @return int
     */
    protected function cleanupTable(string $tableName, array $contentTables, bool $isDryRun): int
    {
        $this->line("📋 Processing {$tableName}...");
        
        $totalCleaned = 0;
        
        foreach ($contentTables as $contentType => $targetTable) {
            $cleanedCount = $this->cleanupOrphanedRecordsForContentType(
                $tableName,
                $contentType,
                $targetTable,
                $isDryRun
            );
            
            if ($cleanedCount > 0) {
                $action = $isDryRun ? 'would clean' : 'cleaned';
                $this->line("  ├─ {$contentType}: {$cleanedCount} orphaned records {$action}");
            }
            
            $totalCleaned += $cleanedCount;
        }

        if ($totalCleaned === 0) {
            $this->line("  └─ ✅ No orphaned records found");
        } else {
            $action = $isDryRun ? 'would be cleaned' : 'cleaned';
            $this->line("  └─ 📊 Total: {$totalCleaned} records {$action}");
        }

        return $totalCleaned;
    }

    /**
     * Clean orphaned records for a specific content type
     *
     * @param string $tableName
     * @param string $contentType
     * @param string $targetTable
     * @param bool $isDryRun
     * @return int
     */
    protected function cleanupOrphanedRecordsForContentType(
        string $tableName, 
        string $contentType, 
        string $targetTable, 
        bool $isDryRun
    ): int {
        // First, check if target table exists
        if (!$this->tableExists($targetTable)) {
            $this->warn("⚠️  Target table '{$targetTable}' does not exist, skipping {$contentType}");
            return 0;
        }

        // Find orphaned records
        $orphanedQuery = DB::table($tableName)
            ->where('content_type', $contentType)
            ->whereNotExists(function ($query) use ($targetTable, $tableName) {
                $query->select(DB::raw(1))
                      ->from($targetTable)
                      ->whereRaw("{$targetTable}.id = {$tableName}.content_id");
            });

        // Count orphaned records
        $orphanedCount = $orphanedQuery->count();

        if ($orphanedCount === 0) {
            return 0;
        }

        if ($isDryRun) {
            return $orphanedCount;
        }

        // Delete orphaned records
        $deletedCount = $orphanedQuery->delete();
        
        return $deletedCount;
    }

    /**
     * Check if a table exists
     *
     * @param string $tableName
     * @return bool
     */
    protected function tableExists(string $tableName): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get properly quoted table name for raw queries
     *
     * @param string $tableName
     * @return string
     */
    protected function getTableName(string $tableName): string
    {
        return DB::getTablePrefix() . $tableName;
    }

    /**
     * Clean orphaned operation logs
     *
     * @param bool $isDryRun
     * @return int
     */
    protected function cleanupOperationLogs(bool $isDryRun): int
    {
        $this->line("📋 Processing operation_logs...");
        
        if (!$this->tableExists('operation_logs')) {
            $this->line("  └─ ✅ Table does not exist, skipping");
            return 0;
        }

        $totalCleaned = 0;
        $hasOrphaned = false;

        foreach ($this->operationLogTables as $attachableType => $targetTable) {
            // Skip if target table doesn't exist
            if (!$this->tableExists($targetTable)) {
                continue;
            }

            // Find orphaned records
            $orphanedQuery = DB::table('operation_logs')
                ->where('attachable_type', $attachableType)
                ->whereNotExists(function ($query) use ($targetTable) {
                    $query->select(DB::raw(1))
                          ->from($targetTable)
                          ->whereRaw("{$targetTable}.id = operation_logs.attachable_id");
                });

            // Count orphaned records
            $orphanedCount = $orphanedQuery->count();

            if ($orphanedCount > 0) {
                $hasOrphaned = true;
                $shortType = class_basename($attachableType);
                
                if ($isDryRun) {
                    $this->line("  ├─ {$shortType}: {$orphanedCount} orphaned records would be cleaned");
                } else {
                    $orphanedQuery->delete();
                    $this->line("  ├─ {$shortType}: {$orphanedCount} orphaned records cleaned");
                }
                
                $totalCleaned += $orphanedCount;
            }
        }

        if (!$hasOrphaned) {
            $this->line("  └─ ✅ No orphaned records found");
        } else {
            $this->line("  └─ 📊 Total: {$totalCleaned} records " . ($isDryRun ? "would be cleaned" : "cleaned"));
        }

        return $totalCleaned;
    }

    /**
     * Clean orphaned images
     *
     * @param bool $isDryRun
     * @return int
     */
    protected function cleanupImages(bool $isDryRun): int
    {
        $this->line("📋 Processing images...");

        if (!$this->tableExists('images')) {
            $this->line("  └─ ✅ Table does not exist, skipping");
            return 0;
        }

        $totalCleaned = 0;
        $hasOrphaned = false;

        foreach ($this->imageTables as $attachableType => $targetTable) {
            // Skip if target table doesn't exist
            if (!$this->tableExists($targetTable)) {
                continue;
            }

            // Find orphaned records
            $orphanedQuery = DB::table('images')
                ->where('attachable_type', $attachableType)
                ->whereNotExists(function ($query) use ($targetTable) {
                    $query->select(DB::raw(1))
                          ->from($targetTable)
                          ->whereRaw("{$targetTable}.id = images.attachable_id");
                });

            // Get orphaned records for file deletion
            $orphanedRecords = $orphanedQuery->get();
            $orphanedCount = $orphanedRecords->count();

            if ($orphanedCount > 0) {
                $hasOrphaned = true;
                $shortType = class_basename($attachableType);

                if ($isDryRun) {
                    $this->line("  ├─ {$shortType}: {$orphanedCount} orphaned records would be cleaned");
                } else {
                    // Delete physical files first
                    $deletedFiles = 0;
                    foreach ($orphanedRecords as $record) {
                        if ($this->deleteImageFile($record->path)) {
                            $deletedFiles++;
                        }
                    }

                    // Delete database records
                    $orphanedQuery->delete();
                    $this->line("  ├─ {$shortType}: {$orphanedCount} records cleaned, {$deletedFiles} files deleted");
                }

                $totalCleaned += $orphanedCount;
            }
        }

        if (!$hasOrphaned) {
            $this->line("  └─ ✅ No orphaned records found");
        } else {
            $this->line("  └─ 📊 Total: {$totalCleaned} records " . ($isDryRun ? "would be cleaned" : "cleaned"));
        }

        return $totalCleaned;
    }

    /**
     * Clean images with missing physical files
     *
     * @param bool $isDryRun
     * @return int
     */
    protected function cleanupMissingImageFiles(bool $isDryRun): int
    {
        $this->line("📋 Processing missing image files...");
        
        if (!$this->tableExists('images')) {
            $this->line("  └─ ✅ Table does not exist, skipping");
            return 0;
        }

        // 獲取所有圖片記錄
        $images = DB::table('images')->get(['id', 'path']);
        $totalCleaned = 0;
        $missingFiles = [];

        foreach ($images as $image) {
            if (!$this->imageFileExists($image->path)) {
                $missingFiles[] = $image;
            }
        }

        $missingCount = count($missingFiles);

        if ($missingCount === 0) {
            $this->line("  └─ ✅ No missing image files found");
            return 0;
        }

        if ($isDryRun) {
            $this->line("  ├─ Found {$missingCount} images with missing files");
            $this->line("  └─ 📊 Total: {$missingCount} records would be cleaned");
        } else {
            // 刪除資料庫記錄
            $imageIds = collect($missingFiles)->pluck('id')->toArray();
            $deletedCount = DB::table('images')->whereIn('id', $imageIds)->delete();
            
            $this->line("  ├─ Found {$missingCount} images with missing files");
            $this->line("  └─ 📊 Total: {$deletedCount} records cleaned");
            $totalCleaned = $deletedCount;
        }

        return $totalCleaned;
    }

    /**
     * Check if image file exists
     *
     * @param string $path
     * @return bool
     */
    protected function imageFileExists(string $path): bool
    {
        try {
            if (empty($path)) {
                return false;
            }

            // 跳過外部 URL
            if (str_starts_with($path, 'http')) {
                return true; // 外部圖片不檢查實體檔案
            }

            $clean = ltrim($path, '/');

            if (str_starts_with($clean, 'uploads/')) {
                // Slim 上傳的檔案在 public/uploads/ 目錄
                $publicPath = public_path($clean);
                return file_exists($publicPath);
            } else {
                // Storage 檔案使用 Laravel Storage
                return Storage::exists($clean);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete image file from storage
     *
     * @param string $path
     * @return bool
     */
    protected function deleteImageFile(string $path): bool
    {
        try {
            if (empty($path)) {
                return false;
            }

            // 處理絕對 URL，跳過
            if (str_starts_with($path, 'http')) {
                return false;
            }

            $clean = ltrim($path, '/');
            $deleted = false;

            if (str_starts_with($clean, 'uploads/')) {
                // Slim 上傳的檔案在 public/uploads/ 目錄
                $publicPath = public_path($clean);
                if (file_exists($publicPath)) {
                    unlink($publicPath);
                    $deleted = true;
                }
            } else {
                // Storage 檔案使用 Laravel Storage
                if (Storage::exists($clean)) {
                    Storage::delete($clean);
                    $deleted = true;
                }
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::warning("Failed to delete image file: {$path}", ['error' => $e->getMessage()]);
            return false;
        }
    }
}