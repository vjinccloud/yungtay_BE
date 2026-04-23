<?php

namespace App\Services;

use App\Models\Article;
use App\Models\News;
use App\Models\Drama;
use App\Models\DramaEpisode;
use App\Models\Program;
use App\Models\ProgramEpisode;
use App\Models\Radio;
use App\Models\Live;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Sitemap 服務類別
 *
 * 負責生成靜態 Sitemap XML 檔案
 * 支援分語系、分檔（chunk）處理大量資料
 *
 * 架構：
 * - 中文 sitemap：不帶 ?lang= 參數
 * - 英文 sitemap：帶 ?lang=en 參數
 * - 每個 sitemap 最多 10,000 筆 URL
 * - 檔案存放於 storage/app/public/sitemaps/
 */
class SitemapService
{
    /**
     * 支援的語系
     */
    protected array $locales = ['zh', 'en'];

    /**
     * 每個 Sitemap 最大 URL 數量
     */
    protected int $chunkSize = 10000;

    /**
     * Sitemap 存放目錄
     */
    protected string $sitemapPath = 'sitemaps';

    /**
     * 網站基礎 URL
     */
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('app.url');
    }

    /**
     * 生成所有 Sitemap（主要入口）
     *
     * @return array 生成的檔案列表
     */
    public function generateAll(): array
    {
        $files = [];

        // 確保目錄存在
        Storage::disk('public')->makeDirectory($this->sitemapPath);

        // 清除舊的 sitemap 檔案
        $this->clearOldSitemaps();

        // 1. 靜態頁面（不分語系，內容少）
        $files[] = $this->generateStaticSitemap();

        // 2. 最新消息（不分語系，內容少）
        $files[] = $this->generateNewsSitemap();

        // 3. 新聞文章（分語系 + 分檔）
        $files = array_merge($files, $this->generateArticlesSitemaps());

        // 4. 影音（分語系）
        $files = array_merge($files, $this->generateDramasSitemaps());

        // 5. 節目（分語系）
        $files = array_merge($files, $this->generateProgramsSitemaps());

        // 6. 廣播（分語系）
        $files = array_merge($files, $this->generateRadiosSitemaps());

        // 7. 直播（分語系）
        $files = array_merge($files, $this->generateLivesSitemaps());

        // 8. 生成 Sitemap Index
        $this->generateSitemapIndex($files);

        return $files;
    }

    /**
     * 清除舊的 sitemap 檔案
     */
    protected function clearOldSitemaps(): void
    {
        $files = Storage::disk('public')->files($this->sitemapPath);
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
    }

    /**
     * 生成靜態頁面 Sitemap
     */
    protected function generateStaticSitemap(): string
    {
        $xml = $this->startSitemap();

        $staticPages = [
            ['route' => 'home', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['route' => 'privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'customer-service', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['route' => 'news', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'articles.index', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'drama.index', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'program.index', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'radio.index', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['route' => 'live.index', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['route' => 'search', 'priority' => '0.6', 'changefreq' => 'weekly'],
        ];

        foreach ($staticPages as $page) {
            // 中文版
            $xml .= $this->addUrl(
                route($page['route']),
                now()->toAtomString(),
                $page['changefreq'],
                $page['priority']
            );

            // 英文版
            $xml .= $this->addUrl(
                route($page['route']) . '?lang=en',
                now()->toAtomString(),
                $page['changefreq'],
                $page['priority']
            );
        }

        $xml .= $this->endSitemap();

        $filename = "{$this->sitemapPath}/sitemap-static.xml";
        Storage::disk('public')->put($filename, $xml);

        return $filename;
    }

    /**
     * 生成最新消息 Sitemap
     */
    protected function generateNewsSitemap(): string
    {
        $xml = $this->startSitemap();

        $news = News::where('is_active', true)
            ->orderBy('published_date', 'desc')
            ->get(['id', 'updated_at']);

        foreach ($news as $item) {
            // 中文版
            $xml .= $this->addUrl(
                route('news.show', $item->id),
                $item->updated_at->toAtomString(),
                'daily',
                '0.8'
            );

            // 英文版
            $xml .= $this->addUrl(
                route('news.show', $item->id) . '?lang=en',
                $item->updated_at->toAtomString(),
                'daily',
                '0.8'
            );
        }

        $xml .= $this->endSitemap();

        $filename = "{$this->sitemapPath}/sitemap-news.xml";
        Storage::disk('public')->put($filename, $xml);

        return $filename;
    }

    /**
     * 生成新聞文章 Sitemaps（分語系 + 分檔）
     */
    protected function generateArticlesSitemaps(): array
    {
        $files = [];

        foreach ($this->locales as $locale) {
            $chunkIndex = 1;

            Article::where('is_active', true)
                ->orderBy('publish_date', 'desc')
                ->select(['id', 'updated_at'])
                ->chunk($this->chunkSize, function ($articles) use ($locale, &$chunkIndex, &$files) {
                    $xml = $this->startSitemap();

                    foreach ($articles as $article) {
                        $url = route('articles.show', $article->id);
                        if ($locale === 'en') {
                            $url .= '?lang=en';
                        }

                        $xml .= $this->addUrl(
                            $url,
                            $article->updated_at->toAtomString(),
                            'weekly',
                            '0.7'
                        );
                    }

                    $xml .= $this->endSitemap();

                    $filename = "{$this->sitemapPath}/sitemap-articles-{$locale}-{$chunkIndex}.xml";
                    Storage::disk('public')->put($filename, $xml);
                    $files[] = $filename;

                    $chunkIndex++;
                });
        }

        return $files;
    }

    /**
     * 生成影音 Sitemaps（分語系）
     */
    protected function generateDramasSitemaps(): array
    {
        $files = [];

        foreach ($this->locales as $locale) {
            $xml = $this->startSitemap();

            $dramas = Drama::where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'updated_at']);

            foreach ($dramas as $drama) {
                // 影音列表頁
                $url = route('drama.videos.index', $drama->id);
                if ($locale === 'en') {
                    $url .= '?lang=en';
                }

                $xml .= $this->addUrl(
                    $url,
                    $drama->updated_at->toAtomString(),
                    'weekly',
                    '0.7'
                );

                // 影音集數頁面（集數表沒有 is_active，直接用排序欄位）
                $episodes = DramaEpisode::where('drama_id', $drama->id)
                    ->orderBy('season')
                    ->orderBy('seq')
                    ->get(['id', 'updated_at']);

                foreach ($episodes as $episode) {
                    $episodeUrl = route('drama.video.show', ['dramaId' => $drama->id, 'episodeId' => $episode->id]);
                    if ($locale === 'en') {
                        $episodeUrl .= '?lang=en';
                    }

                    $xml .= $this->addUrl(
                        $episodeUrl,
                        $episode->updated_at->toAtomString(),
                        'monthly',
                        '0.6'
                    );
                }
            }

            $xml .= $this->endSitemap();

            $filename = "{$this->sitemapPath}/sitemap-dramas-{$locale}.xml";
            Storage::disk('public')->put($filename, $xml);
            $files[] = $filename;
        }

        return $files;
    }

    /**
     * 生成節目 Sitemaps（分語系）
     */
    protected function generateProgramsSitemaps(): array
    {
        $files = [];

        foreach ($this->locales as $locale) {
            $xml = $this->startSitemap();

            $programs = Program::where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'updated_at']);

            foreach ($programs as $program) {
                // 節目列表頁
                $url = route('program.videos.index', $program->id);
                if ($locale === 'en') {
                    $url .= '?lang=en';
                }

                $xml .= $this->addUrl(
                    $url,
                    $program->updated_at->toAtomString(),
                    'weekly',
                    '0.7'
                );

                // 節目集數頁面（集數表沒有 is_active，直接用排序欄位）
                $episodes = ProgramEpisode::where('program_id', $program->id)
                    ->orderBy('season')
                    ->orderBy('seq')
                    ->get(['id', 'updated_at']);

                foreach ($episodes as $episode) {
                    $episodeUrl = route('program.video.show', ['programId' => $program->id, 'episodeId' => $episode->id]);
                    if ($locale === 'en') {
                        $episodeUrl .= '?lang=en';
                    }

                    $xml .= $this->addUrl(
                        $episodeUrl,
                        $episode->updated_at->toAtomString(),
                        'monthly',
                        '0.6'
                    );
                }
            }

            $xml .= $this->endSitemap();

            $filename = "{$this->sitemapPath}/sitemap-programs-{$locale}.xml";
            Storage::disk('public')->put($filename, $xml);
            $files[] = $filename;
        }

        return $files;
    }

    /**
     * 生成廣播 Sitemaps（分語系）
     */
    protected function generateRadiosSitemaps(): array
    {
        $files = [];

        foreach ($this->locales as $locale) {
            $xml = $this->startSitemap();

            $radios = Radio::where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'updated_at']);

            foreach ($radios as $radio) {
                $url = route('radio.show', $radio->id);
                if ($locale === 'en') {
                    $url .= '?lang=en';
                }

                $xml .= $this->addUrl(
                    $url,
                    $radio->updated_at->toAtomString(),
                    'weekly',
                    '0.7'
                );
            }

            $xml .= $this->endSitemap();

            $filename = "{$this->sitemapPath}/sitemap-radios-{$locale}.xml";
            Storage::disk('public')->put($filename, $xml);
            $files[] = $filename;
        }

        return $files;
    }

    /**
     * 生成直播 Sitemaps（分語系）
     */
    protected function generateLivesSitemaps(): array
    {
        $files = [];

        foreach ($this->locales as $locale) {
            $xml = $this->startSitemap();

            $lives = Live::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'updated_at']);

            foreach ($lives as $live) {
                $url = route('live.index', ['id' => $live->id]);
                if ($locale === 'en') {
                    $url .= '&lang=en';
                }

                $xml .= $this->addUrl(
                    $url,
                    $live->updated_at->toAtomString(),
                    'daily',
                    '0.8'
                );
            }

            $xml .= $this->endSitemap();

            $filename = "{$this->sitemapPath}/sitemap-lives-{$locale}.xml";
            Storage::disk('public')->put($filename, $xml);
            $files[] = $filename;
        }

        return $files;
    }

    /**
     * 生成 Sitemap Index
     */
    protected function generateSitemapIndex(array $files): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($files as $file) {
            // 從 sitemaps/sitemap-xxx.xml 取出檔名部分
            $filename = basename($file);
            // 使用乾淨的 URL 格式：/sitemaps/{filename}
            $url = url('/sitemaps/' . $filename);
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>{$url}</loc>\n";
            $xml .= "    <lastmod>" . now()->toAtomString() . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        Storage::disk('public')->put("{$this->sitemapPath}/sitemap.xml", $xml);
    }

    /**
     * 開始 Sitemap XML
     */
    protected function startSitemap(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
               '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    }

    /**
     * 結束 Sitemap XML
     */
    protected function endSitemap(): string
    {
        return '</urlset>';
    }

    /**
     * 添加 URL 到 Sitemap
     */
    protected function addUrl(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        $escapedLoc = htmlspecialchars($loc, ENT_XML1, 'UTF-8');

        return "  <url>\n" .
               "    <loc>{$escapedLoc}</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }

    /**
     * 取得 Sitemap 檔案路徑
     */
    public function getSitemapPath(): string
    {
        return $this->sitemapPath;
    }

    /**
     * 取得所有已生成的 Sitemap 檔案
     */
    public function getGeneratedFiles(): array
    {
        return Storage::disk('public')->files($this->sitemapPath);
    }
}