<?php
// app/Services/Thumbnail/Contracts/ThumbnailStrategyInterface.php

namespace App\Services\Thumbnail\Contracts;

use App\Models\DramaEpisode;
use App\Models\ProgramEpisode;

interface ThumbnailStrategyInterface
{
    /**
     * 判斷是否支援此影片類型
     *
     * @param DramaEpisode|ProgramEpisode $episode
     * @return bool
     */
    public function supports($episode): bool;

    /**
     * 生成縮圖
     *
     * @param DramaEpisode|ProgramEpisode $episode
     * @return string|null 返回縮圖檔案路徑，失敗返回 null
     */
    public function generate($episode): ?string;

    /**
     * 取得策略名稱
     *
     * @return string
     */
    public function getName(): string;
}