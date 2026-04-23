<?php

namespace App\Services;

use Illuminate\Support\Facades\App;

class JsonLdService
{
    private array $siteInfo;

    public function __construct()
    {
        // 取得網站資訊，如果沒有就使用空陣列
        $this->siteInfo = app(\App\Repositories\WebsiteInfoRepository::class)->getWebsiteInfoForFrontend() ?? [];
    }
    /**
     * 取得統一的 Publisher 資訊（TVStation 類型）
     */
    private function getPublisher(): array
    {
        return [
            '@type' => 'TVStation',
            'name' => $this->siteInfo['title'] ?? config('app.name', 'SJTV'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $this->siteInfo['website_icon'] ? asset($this->siteInfo['website_icon']) : asset('frontend/images/default.webp'),
                'width' => 200,
                'height' => 200
            ],
            'url' => $this->siteInfo['website_url'] ?? config('app.url')
        ];
    }

    /**
     * 格式化日期為 JSON-LD 標準格式（ISO 8601 帶台灣時區）
     */
    private function formatDateForJsonLd($date): ?string
    {
        if (!$date) {
            return null;
        }

        // 如果是字串日期，先轉換為 Carbon 實例
        if (is_string($date)) {
            try {
                $date = \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        }

        // 如果是 Carbon 實例，格式化為 ISO 8601 帶台灣時區（+08:00）
        if ($date instanceof \Carbon\Carbon) {
            return $date->setTimezone('Asia/Taipei')->format('c');
        }

        return null;
    }

    /**
     * 生成適合 JSON-LD 的標題（限制 110 字元）
     */
    private function generateHeadline(?string $title): string
    {
        if (!$title) {
            return '';
        }

        // 移除多餘空白和換行
        $cleanTitle = preg_replace('/\s+/', ' ', trim($title));

        // 如果標題長度在 110 字元內，直接返回
        if (mb_strlen($cleanTitle) <= 110) {
            return $cleanTitle;
        }

        // 截斷到 107 字元並加上省略號
        return mb_substr($cleanTitle, 0, 107) . '...';
    }

    /**
     * 生成適合 JSON-LD 的描述摘要（去除新聞慣例開頭，限制 160 字元）
     */
    private function generateDescription(?string $content): string
    {
        if (!$content) {
            return '';
        }

        // 移除 HTML 標籤
        $rawDescription = strip_tags($content);

        // 移除多餘空白和換行
        $rawDescription = preg_replace('/\s+/', ' ', trim($rawDescription));

        // 移除新聞常見開頭（例如：中央社記者XXX台北X日電、（中央社記者...電））
        $cleanDescription = preg_replace('/^（?中央社記者.+?[0-9]{1,2}日電）?/', '', $rawDescription);

        // 移除其他常見新聞開頭格式
        $cleanDescription = preg_replace('/^（?記者.+?報導）?/', '', $cleanDescription);
        $cleanDescription = preg_replace('/^（?.+?記者.+?[0-9]{1,2}日.+?）/', '', $cleanDescription);

        // 清理開頭空白
        $cleanDescription = trim($cleanDescription);

        // 如果清理後內容為空，使用原始內容的前段
        if (empty($cleanDescription)) {
            $cleanDescription = $rawDescription;
        }

        // 最多取 160 個字元
        if (mb_strlen($cleanDescription) <= 160) {
            return $cleanDescription;
        }

        // 截斷到 160 字元
        $truncated = mb_substr($cleanDescription, 0, 160);

        // 在最後一個句號處截斷（如果有的話且位置合理）
        $lastPeriod = mb_strrpos($truncated, '。');
        if ($lastPeriod !== false && $lastPeriod > 50) {
            return mb_substr($truncated, 0, $lastPeriod + 1);
        }

        // 沒有句號就加省略號
        return rtrim($truncated) . '...';
    }

    /**
     * 生成新聞文章作者資訊，根據 RSS 來源判定類型
     */
    private function generateArticleAuthor(array $article): array
    {
        $locale = App::getLocale();
        $author = is_array($article['author'] ?? null)
            ? ($article['author'][$locale] ?? $article['author']['zh_TW'] ?? '')
            : ($article['author'] ?? '');

        // 判斷是否為 RSS 來源（有 source_provider 和 source_guid_hash 表示來自 RSS）
        $isRssSource = !empty($article['source_provider']) && !empty($article['source_guid_hash']);

        if ($isRssSource) {
            // RSS 來源：使用機構/公司名稱
            return [
                '@type' => 'Organization',
                'name' => $article['source_provider'] ?? config('app.name', 'SJTV')
            ];
        } elseif ($author) {
            // 非 RSS 來源且有作者：使用個人
            return [
                '@type' => 'Person',
                'name' => $author
            ];
        } else {
            // 非 RSS 來源且無作者：使用網站名稱作為組織
            return [
                '@type' => 'Organization',
                'name' => config('app.name', 'SJTV')
            ];
        }
    }

    /**
     * 生成 NewsArticle JSON-LD 結構化資料
     */
    public function generateNewsArticle(array $article): array
    {
        $locale = App::getLocale();

        // 取得當前語系的標題和內容
        $title = is_array($article['title']) ? ($article['title'][$locale] ?? $article['title']['zh_TW'] ?? '') : $article['title'];
        $content = is_array($article['content']) ? ($article['content'][$locale] ?? $article['content']['zh_TW'] ?? '') : $article['content'];
        $author = is_array($article['author']) ? ($article['author'][$locale] ?? $article['author']['zh_TW'] ?? '') : ($article['author'] ?? '');
        $location = is_array($article['location']) ? ($article['location'][$locale] ?? $article['location']['zh_TW'] ?? '') : ($article['location'] ?? '');
        $tags = is_array($article['tags']) ? ($article['tags'][$locale] ?? $article['tags']['zh_TW'] ?? '') : ($article['tags'] ?? '');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $this->generateHeadline($title),
            'description' => $this->generateDescription($content),
            'image' => [$article['jsonld_image'] ?: asset('frontend/images/default.webp')],
            'author' => $this->generateArticleAuthor($article),
            'publisher' => $this->getPublisher(),
            'datePublished' => $this->formatDateForJsonLd($article['jsonld_publish_date'] ?? $article['created_at']),
            'dateModified' => $this->formatDateForJsonLd($article['updated_at'] ?? $article['created_at']),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url()->current()
            ],
            'inLanguage' => str_replace('_', '-', $locale),
            'contentLocation' => $location ? [
                '@type' => 'Place',
                'name' => $location
            ] : null,
            'keywords' => $tags
        ];
    }

    /**
     * 生成最新消息 JSON-LD 結構化資料（News Model）
     */
    public function generateNews(array $news): array
    {
        $locale = App::getLocale();

        // 取得當前語系的資料
        $title = is_array($news['title']) ? ($news['title'][$locale] ?? $news['title']['zh_TW'] ?? '') : ($news['title'] ?? '');
        $content = is_array($news['content']) ? ($news['content'][$locale] ?? $news['content']['zh_TW'] ?? '') : ($news['content'] ?? '');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->generateHeadline($title),
            'description' => $this->generateDescription(strip_tags($content)),
            'image' => isset($news['jsonld_image']) ? (array) $news['jsonld_image'] : [asset('frontend/images/default.webp')],
            'author' => [
                '@type' => 'Organization',
                'name' => $this->siteInfo['title'] ?? config('app.name', 'SJTV')
            ],
            'publisher' => $this->getPublisher(),
            'datePublished' => $this->formatDateForJsonLd($news['published_date'] ?? null),
            'dateModified' => $this->formatDateForJsonLd($news['created_at'] ?? $news['published_date']),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url()->current()
            ],
            'inLanguage' => str_replace('_', '-', $locale)
        ];
    }

    /**
     * 生成最新消息集合頁面 JSON-LD 結構化資料（最新消息用）
     */
    public function generateLatestNewsCollectionPage(array $newsList, array $pageInfo = [], array $metaOverride = []): array
    {
        $locale = App::getLocale();
        $itemListElement = [];

        foreach ($newsList as $index => $news) {
            $title = is_array($news['title']) ? ($news['title'][$locale] ?? $news['title']['zh_TW'] ?? '') : ($news['title'] ?? '');
            $content = is_array($news['content']) ? ($news['content'][$locale] ?? $news['content']['zh_TW'] ?? '') : ($news['content'] ?? '');

            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'item' => [
                    '@type' => 'Article',
                    '@id' => route('news.show', $news['id']),
                    'headline' => $this->generateHeadline($title),
                    'description' => $this->generateDescription(strip_tags($content)),
                    'image' => isset($news['jsonld_image']) ? (array) $news['jsonld_image'] : [asset('frontend/images/default.webp')],
                    'author' => [
                        '@type' => 'Organization',
                        'name' => $this->siteInfo['title'] ?? config('app.name', 'SJTV')
                    ],
                    'datePublished' => $this->formatDateForJsonLd($news['published_date'] ?? $news['created_at']),
                    'dateModified' => $this->formatDateForJsonLd($news['updated_at'] ?? $news['created_at']),
                    'url' => route('news.show', $news['id'])
                ]
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $pageInfo['name'] ?? __('frontend.nav.latest_news'),
            'description' => $metaOverride['description'] ?? $this->siteInfo['description'] ?? null,
            'url' => $pageInfo['url'] ?? url()->current(),
            'inLanguage' => str_replace('_', '-', $locale),
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => url('/')
            ],
            'publisher' => $this->getPublisher(),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $itemListElement,
                'numberOfItems' => count($itemListElement)
            ]
        ];
    }

    /**
     * 生成媒體集合頁面 JSON-LD 結構化資料（主題+影音/節目結構）
     */
    public function generateMediaCollectionPage(array $themes, string $contentType, array $pageInfo = [], array $metaOverride = []): array
    {
        $locale = App::getLocale();
        $itemListElement = [];
        $position = 1;

        // 根據內容類型決定 Schema.org 類型和路由前綴
        $schemaType = $contentType === 'drama' ? 'TVSeries' : 'TVSeason';
        $routePrefix = $contentType === 'drama' ? 'drama.videos.index' : 'program.videos.index';
        $dateField = $contentType === 'drama' ? 'datePublished' : 'startDate';

        foreach ($themes as $theme) {
            if (!empty($theme['items'])) {
                foreach ($theme['items'] as $item) {
                    $title = is_array($item['title']) ? ($item['title'][$locale] ?? $item['title']['zh_TW'] ?? '') : ($item['title'] ?? '');
                    $description = isset($item['description']) ? (is_array($item['description']) ? ($item['description'][$locale] ?? $item['description']['zh_TW'] ?? '') : ($item['description'] ?? '')) : '';
                    $cast = isset($item['cast']) ? (is_array($item['cast']) ? ($item['cast'][$locale] ?? $item['cast']['zh_TW'] ?? '') : ($item['cast'] ?? '')) : '';
                    $crew = isset($item['crew']) ? (is_array($item['crew']) ? ($item['crew'][$locale] ?? $item['crew']['zh_TW'] ?? '') : ($item['crew'] ?? '')) : '';

                    $routeParam = $contentType === 'drama' ? 'dramaId' : 'programId';
                    $itemData = [
                        '@type' => $schemaType,
                        '@id' => route($routePrefix, [$routeParam => $item['id']]),
                        'name' => $this->generateHeadline($title),
                        'description' => $description ? $this->generateDescription(strip_tags($description)) : null,
                        'image' => isset($item['poster_desktop']) ? [asset($item['poster_desktop'])] : [asset('frontend/images/default.webp')],
                        'genre' => $theme['name'] ?? null,
                        $dateField => $this->formatDateForJsonLd($item['published_date'] ?? $item['created_at'] ?? null),
                        'datePublished' => $this->formatDateForJsonLd($item['published_date'] ?? $item['created_at'] ?? null),
                        'dateModified' => $this->formatDateForJsonLd($item['updated_at'] ?? $item['created_at'] ?? null),
                        'url' => route($routePrefix, [$routeParam => $item['id']])
                    ];

                    // 只有影音需要演員和製作人員資訊
                    if ($contentType === 'drama') {
                        if ($cast) {
                            $itemData['actor'] = [
                                '@type' => 'Person',
                                'name' => $cast
                            ];
                        }
                        if ($crew) {
                            $itemData['director'] = [
                                '@type' => 'Person',
                                'name' => $crew
                            ];
                        }
                    }

                    $itemListElement[] = [
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'item' => $itemData
                    ];
                }
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $pageInfo['name'] ?? ($contentType === 'drama' ? __('messages.page_title.drama_type') : __('messages.page_title.program_type')),
            'description' => $metaOverride['description'] ?? $this->siteInfo['description'] ?? null,
            'url' => $pageInfo['url'] ?? url()->current(),
            'datePublished' => $this->formatDateForJsonLd(now()),
            'dateModified' => $this->formatDateForJsonLd(now()),
            'inLanguage' => str_replace('_', '-', $locale),
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => url('/')
            ],
            'publisher' => $this->getPublisher(),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $itemListElement,
                'numberOfItems' => count($itemListElement)
            ]
        ];
    }

    /**
     * 生成影音集合頁面 JSON-LD 結構化資料（向後相容）
     */
    public function generateDramaCollectionPage(array $themes, array $pageInfo = [], array $metaOverride = []): array
    {
        return $this->generateMediaCollectionPage($themes, 'drama', $pageInfo, $metaOverride);
    }

    /**
     * 生成節目集合頁面 JSON-LD 結構化資料（向後相容）
     */
    public function generateProgramCollectionPage(array $themes, array $pageInfo = [], array $metaOverride = []): array
    {
        return $this->generateMediaCollectionPage($themes, 'program', $pageInfo, $metaOverride);
    }

    /**
     * 生成廣播集合頁面 JSON-LD 結構化資料
     *
     * 廣播與影音/節目的差異：
     * - 使用 RadioSeries 類型（非 TVSeries/TVSeason）
     * - 路由結構：radio.show（非 videos.index）
     * - 圖片欄位：image（非 poster_desktop/poster_mobile）
     * - 年份欄位：year（非 release_year）
     * - 發布日期：publish_date（非 published_date）
     */
    public function generateRadioCollectionPage(array $themes, array $pageInfo = [], array $metaOverride = []): array
    {
        $locale = App::getLocale();
        $itemListElement = [];
        $position = 1;

        foreach ($themes as $theme) {
            if (!empty($theme['items'])) {
                foreach ($theme['items'] as $item) {
                    $title = is_array($item['title'] ?? '')
                        ? ($item['title'][$locale] ?? $item['title']['zh_TW'] ?? '')
                        : ($item['title'] ?? '');

                    // 廣播使用 media_name 作為描述，備援 description
                    $description = '';
                    if (!empty($item['media_name'])) {
                        $description = is_array($item['media_name'])
                            ? ($item['media_name'][$locale] ?? $item['media_name']['zh_TW'] ?? '')
                            : $item['media_name'];
                    } elseif (!empty($item['description'])) {
                        $description = is_array($item['description'])
                            ? ($item['description'][$locale] ?? $item['description']['zh_TW'] ?? '')
                            : $item['description'];
                    }

                    $itemData = [
                        '@type' => 'RadioSeries',
                        '@id' => route('radio.show', $item['id']),
                        'name' => $this->generateHeadline($title),
                        'description' => $description ? $this->generateDescription(strip_tags($description)) : null,
                        'image' => isset($item['image']) ? [asset($item['image'])] : [asset('frontend/images/default.webp')],
                        'genre' => $theme['name'] ?? null,
                        'datePublished' => $this->formatDateForJsonLd($item['publish_date'] ?? $item['created_at'] ?? null),
                        'dateModified' => $this->formatDateForJsonLd($item['updated_at'] ?? $item['created_at'] ?? null),
                        'url' => route('radio.show', $item['id'])
                    ];

                    $itemListElement[] = [
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'item' => $itemData
                    ];
                }
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $pageInfo['name'] ?? __('frontend.nav.radio'),
            'description' => $metaOverride['description'] ?? $this->siteInfo['description'] ?? null,
            'url' => $pageInfo['url'] ?? url()->current(),
            'datePublished' => $this->formatDateForJsonLd(now()),
            'dateModified' => $this->formatDateForJsonLd(now()),
            'inLanguage' => str_replace('_', '-', $locale),
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => url('/')
            ],
            'publisher' => $this->getPublisher(),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $itemListElement,
                'numberOfItems' => count($itemListElement)
            ]
        ];
    }

    /**
     * 統一生成媒體內容（影音/節目）的 JSON-LD 結構化資料
     */
    public function generateMediaContent(string $contentType, array $content): array
    {
        $locale = App::getLocale();

        // 取得當前語系的資料
        $title = is_array($content['title']) ? ($content['title'][$locale] ?? $content['title']['zh_TW'] ?? '') : ($content['title'] ?? '');
        $description = $content['description'] ?? '';
        $cast = $content['cast'] ?? '';
        $crew = $content['crew'] ?? '';

        // 計算集數
        $episodeCount = 0;
        if (isset($content['episodes']) && is_countable($content['episodes'])) {
            // 如果 episodes 是多維陣列（按季分組）
            if (is_array($content['episodes'])) {
                foreach ($content['episodes'] as $seasonEpisodes) {
                    if (is_countable($seasonEpisodes)) {
                        $episodeCount += count($seasonEpisodes);
                    }
                }
            }
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => $contentType === 'program' ? 'TVSeason' : 'TVSeries',
            'name' => $this->generateHeadline($title),
            'description' => $description ? strip_tags($description) : null,
            'genre' => isset($content['category']) ? (
                is_array($content['category']['name']) ?
                ($content['category']['name'][$locale] ?? $content['category']['name']['zh_TW'] ?? '') :
                $content['category']['name']
            ) : null,
            'numberOfEpisodes' => $episodeCount,
            'startDate' => $this->formatDateForJsonLd($content['published_date'] ?? $content['created_at'] ?? null),
            'datePublished' => $this->formatDateForJsonLd($content['published_date'] ?? $content['created_at'] ?? null),
            'dateModified' => $this->formatDateForJsonLd($content['updated_at'] ?? $content['created_at'] ?? null),
            'productionCompany' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'SJTV')
            ],
            'inLanguage' => str_replace('_', '-', $locale),
            'url' => url()->current(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url()->current()
            ]
        ];

        // 影音和節目都有 numberOfSeasons
        $jsonLd['numberOfSeasons'] = $content['season_number'] ?? 1;

        // 加入封面圖片（Repository 返回的是相對路徑，需要加上 asset()）
        $images = [];
        if (!empty($content['poster_desktop'])) {
            // 檢查是否已經是完整的 URL（例如從靜態資源或外部來源）
            $posterDesktop = filter_var($content['poster_desktop'], FILTER_VALIDATE_URL)
                ? $content['poster_desktop']
                : asset($content['poster_desktop']);
            $images[] = $posterDesktop;
        }
        if (!empty($content['poster_mobile'])) {
            $posterMobile = filter_var($content['poster_mobile'], FILTER_VALIDATE_URL)
                ? $content['poster_mobile']
                : asset($content['poster_mobile']);
            $images[] = $posterMobile;
        }
        if (!empty($content['banner_desktop'])) {
            $bannerDesktop = filter_var($content['banner_desktop'], FILTER_VALIDATE_URL)
                ? $content['banner_desktop']
                : asset($content['banner_desktop']);
            $images[] = $bannerDesktop;
        }
        if (!empty($images)) {
            $jsonLd['image'] = count($images) === 1 ? $images[0] : $images;
        }

        // 處理季節資訊 (containsSeason)
        if (isset($content['seasonInfo']) && is_array($content['seasonInfo'])) {
            $seasons = [];
            foreach ($content['seasonInfo'] as $season) {
                if (isset($season['season']) && isset($season['episode_count'])) {
                    $seasonData = [
                        '@type' => 'TVSeason',
                        'seasonNumber' => $season['season'],
                        'numberOfEpisodes' => $season['episode_count']
                    ];

                    // 如果有該季的發布日期可以加入
                    if (isset($season['published_date'])) {
                        $seasonData['datePublished'] = $this->formatDateForJsonLd($season['published_date']);
                    }

                    $seasons[] = $seasonData;
                }
            }

            if (!empty($seasons)) {
                $jsonLd['containsSeason'] = count($seasons) === 1 ? $seasons[0] : $seasons;
            }
        }

        // 處理演員資訊
        if ($cast) {
            $jsonLd['actor'] = [
                '@type' => 'Person',
                'name' => $cast
            ];
        }

        // 處���導演/主持人資訊
        if ($crew) {
            $jsonLd[$contentType === 'program' ? 'host' : 'director'] = [
                '@type' => 'Person',
                'name' => $crew
            ];
        }

        // 處理關鍵字
        if (isset($content['tags'])) {
            $jsonLd['keywords'] = is_array($content['tags']) ? implode(', ', $content['tags']) : $content['tags'];
        }

        return $jsonLd;
    }

    /**
     * 將時長文字轉換為 ISO 8601 格式
     */
    private function convertDurationToISO8601(?string $duration): ?string
    {
        if (empty($duration)) {
            return null;
        }

        // 嘗試從常見格式解析時長
        // 格式如：30分鐘、1小時30分、45min、1h 30m 等
        preg_match_all('/(\d+)\s*(?:小時|時|hour?s?|h)\s*(?:(\d+)\s*(?:分鐘?|min?s?|m))?/i', $duration, $hours);
        preg_match_all('/(\d+)\s*(?:分鐘?|min?s?|m)(?!\w)/i', $duration, $minutes);
        preg_match_all('/(\d+)\s*(?:秒|sec?s?|s)(?!\w)/i', $duration, $seconds);

        $totalMinutes = 0;
        $totalSeconds = 0;

        // 處理小時和分鐘
        if (!empty($hours[1][0])) {
            $totalMinutes += (int)$hours[1][0] * 60; // 小時轉分鐘
            if (!empty($hours[2][0])) {
                $totalMinutes += (int)$hours[2][0]; // 加上分鐘
            }
        } elseif (!empty($minutes[1])) {
            // 只有分鐘
            $totalMinutes = (int)$minutes[1][0];
        }

        // 處理秒
        if (!empty($seconds[1][0])) {
            $totalSeconds = (int)$seconds[1][0];
        }

        // 如果沒有解析到任何時間，返回 null
        if ($totalMinutes === 0 && $totalSeconds === 0) {
            return null;
        }

        // 構建 ISO 8601 格式
        $iso8601 = 'PT';

        if ($totalMinutes > 0) {
            $hours = intval($totalMinutes / 60);
            $minutes = $totalMinutes % 60;

            if ($hours > 0) {
                $iso8601 .= $hours . 'H';
            }
            if ($minutes > 0) {
                $iso8601 .= $minutes . 'M';
            }
        }

        if ($totalSeconds > 0) {
            $iso8601 .= $totalSeconds . 'S';
        }

        return $iso8601 === 'PT' ? null : $iso8601;
    }

    /**
     * 生成單集影片的 VideoObject + TVEpisode JSON-LD 結構化資料
     */
    public function generateVideoObject(array $content, array $episode, string $contentType = 'drama'): array
    {
        $locale = App::getLocale();

        // 取得內容標題
        $contentTitle = is_array($content['title'])
            ? ($content['title'][$locale] ?? $content['title']['zh_TW'] ?? '')
            : ($content['title'] ?? '');

        // 組合完整標題：影音/節目名稱 - 第X集
        $episodeTitle = $contentTitle . ' - ' . __('frontend.video.episode', ['number' => $episode['seq'] ?? 1]);

        // 取得影片描述（優先使用集數描述，否則使用內容描述）
        $description = $episode['description'] ?? $content['description'] ?? '';

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => ['VideoObject', 'TVEpisode'], // 同時是 VideoObject 和 TVEpisode
            'name' => $episodeTitle,
            'description' => $description ? strip_tags($description) : null,
            'uploadDate' => $this->formatDateForJsonLd($episode['created_at'] ?? $content['published_date'] ?? null),
            'datePublished' => $this->formatDateForJsonLd($episode['published_date'] ?? $content['published_date'] ?? null),
            'dateModified' => $this->formatDateForJsonLd($episode['updated_at'] ?? $content['updated_at'] ?? null),
            'duration' => $this->convertDurationToISO8601($episode['duration_text'] ?? null),
            'url' => url()->current(),
            'inLanguage' => str_replace('_', '-', $locale),
        ];

        // 加入縮圖
        $thumbnailUrl = null;
        if (!empty($episode['thumbnail'])) {
            $thumbnailUrl = filter_var($episode['thumbnail'], FILTER_VALIDATE_URL)
                ? $episode['thumbnail']
                : asset($episode['thumbnail']);
        } elseif (!empty($content['poster_desktop'])) {
            $thumbnailUrl = filter_var($content['poster_desktop'], FILTER_VALIDATE_URL)
                ? $content['poster_desktop']
                : asset($content['poster_desktop']);
        }

        if ($thumbnailUrl) {
            $jsonLd['thumbnailUrl'] = $thumbnailUrl;
            $jsonLd['thumbnail'] = [
                '@type' => 'ImageObject',
                'url' => $thumbnailUrl
            ];
        }

        // 如果是 YouTube 影片，加入嵌入資訊
        if ($episode['video_type'] === 'youtube' && !empty($episode['video_embed_url'])) {
            $jsonLd['embedUrl'] = $episode['video_embed_url'];
        }

        // 加入內容 URL（如果是上傳的影片）
        if ($episode['video_type'] === 'upload' && !empty($episode['video_url'])) {
            $jsonLd['contentUrl'] = $episode['video_url'];
        }

        // 關聯到主要系列
        $jsonLd['partOfSeries'] = [
            '@type' => $contentType === 'drama' ? 'TVSeries' : 'TVSeason',
            'name' => $contentTitle,
            'url' => route($contentType . '.videos.index', [$contentType === 'drama' ? 'dramaId' : 'programId' => $content['id']])
        ];

        // 加入季數和集數資訊
        if (isset($episode['season'])) {
            $jsonLd['partOfSeason'] = [
                '@type' => 'TVSeason',
                'seasonNumber' => $episode['season'],
                'name' => $contentTitle . ' - ' . __('frontend.video.season_number', ['number' => $episode['season']])
            ];
        }

        $jsonLd['episodeNumber'] = $episode['seq'] ?? null;

        // 加入 mainEntityOfPage（指向 canonical 頁面 URL）
        $jsonLd['mainEntityOfPage'] = [
            '@type' => 'WebPage',
            '@id' => url()->current()
        ];

        // 加入分類標籤（genre）
        if (isset($content['category'])) {
            if (is_array($content['category']) && isset($content['category']['name'])) {
                // 如果是完整的 category 陣列
                $genre = is_array($content['category']['name'])
                    ? ($content['category']['name'][$locale] ?? $content['category']['name']['zh_TW'] ?? '')
                    : $content['category']['name'];
                $jsonLd['genre'] = $genre;
            } elseif (is_string($content['category'])) {
                // 如果是字串
                $jsonLd['genre'] = $content['category'];
            }
        }

        // 發布者資訊
        $jsonLd['publisher'] = $this->getPublisher();

        // 演員和導演資訊（從主內容取得）
        if (!empty($content['cast'])) {
            $jsonLd['actor'] = [
                '@type' => 'Person',
                'name' => $content['cast']
            ];
        }

        if (!empty($content['crew'])) {
            $jsonLd['director'] = [
                '@type' => 'Person',
                'name' => $content['crew']
            ];
        }

        // 關鍵字
        if (!empty($content['tags'])) {
            $jsonLd['keywords'] = is_array($content['tags']) ? implode(', ', $content['tags']) : $content['tags'];
        }

        return $jsonLd;
    }

    /**
     * 生成 TVSeries JSON-LD 結構化資料（向後相容）
     */
    public function generateTVSeries(array $drama): array
    {
        return $this->generateMediaContent('drama', $drama);
    }

    /**
     * 生成 TVSeason JSON-LD 結構化資料（向後相容）
     */
    public function generateTVSeason(array $program): array
    {
        return $this->generateMediaContent('program', $program);
    }

    /**
     * 生成 BroadcastEvent JSON-LD 結構化資料（直播用）
     */
    public function generateBroadcastEvent(array $live): array
    {
        $locale = App::getLocale();

        // 取得當前語系的資料
        $title = is_array($live['title']) ? ($live['title'][$locale] ?? $live['title']['zh_TW'] ?? '') : ($live['title'] ?? '');
        $description = isset($live['description'])
            ? (is_array($live['description']) ? ($live['description'][$locale] ?? $live['description']['zh_TW'] ?? '') : $live['description'])
            : '';

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BroadcastEvent',
            'name' => $this->generateHeadline($title),
            'description' => strip_tags($description),
            'isLiveBroadcast' => true,
            'broadcastOfEvent' => [
                '@type' => 'Event',
                'name' => $this->generateHeadline($title),
                'description' => strip_tags($description)
            ],
            'publishedOn' => [
                '@type' => 'BroadcastService',
                'name' => config('app.name', 'SJTV'),
                'url' => url('/')
            ],
            'videoFormat' => 'application/x-shockwave-flash',
            'embedUrl' => $live['youtube_url'] ?? null,
            'inLanguage' => str_replace('_', '-', $locale),
            'url' => url()->current(),
            'keywords' => $live['tags'] ?? null
        ];
    }

    /**
     * 生成 RadioSeries JSON-LD 結構化資料（廣播用）
     */
    public function generateRadioSeries(array $radio): array
    {
        $locale = App::getLocale();

        // 取得當前語系的資料
        $title = is_array($radio['title']) ? ($radio['title'][$locale] ?? $radio['title']['zh_TW'] ?? '') : ($radio['title'] ?? '');
        $description = isset($radio['description'])
            ? (is_array($radio['description']) ? ($radio['description'][$locale] ?? $radio['description']['zh_TW'] ?? '') : $radio['description'])
            : ($radio['media_name'] ?? '');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'RadioSeries',
            'name' => $this->generateHeadline($title),
            'description' => !empty($description) ? strip_tags($description) : null,
            'image' => !empty($radio['image']) ? asset($radio['image']) : null,
            'genre' => isset($radio['category']) ? (
                is_array($radio['category']) ? (
                    isset($radio['category']['name']) ? (
                        is_array($radio['category']['name']) ?
                        ($radio['category']['name'][$locale] ?? $radio['category']['name']['zh_TW'] ?? '') :
                        $radio['category']['name']
                    ) : null
                ) : $radio['category']
            ) : null,
            'author' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'SJTV')
            ],
            'publisher' => $this->getPublisher(),
            'datePublished' => $this->formatDateForJsonLd(
                $radio['publish_date_raw'] ?? $radio['created_at']
            ),
            'dateModified' => $this->formatDateForJsonLd(
                $radio['updated_at'] ?? $radio['created_at']
            ),
            'inLanguage' => str_replace('_', '-', $locale),
            'url' => url()->current(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url()->current()
            ],
            'keywords' => $radio['tags'] ?? null
        ];
    }

    /**
     * 將陣列轉換為 JSON-LD 字串
     */
    public function toJsonLd(array $data): string
    {
        // 遞歸移除 null 值（保留合法的 0、false、空字串）
        $cleanData = $this->removeNullValues($data);

        return '<script type="application/ld+json">' .
               json_encode($cleanData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
               '</script>';
    }

    /**
     * 遞歸移除陣列中的 null 值
     */
    private function removeNullValues(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue; // 跳過 null 值
            }

            if (is_array($value)) {
                $cleaned = $this->removeNullValues($value);
                if (!empty($cleaned)) { // 只保留非空陣列
                    $result[$key] = $cleaned;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 生成 WebSite JSON-LD 結構化資料（首頁用）
     */
    public function generateWebSite(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteInfo['title'] ?? config('app.name', 'SJTV'),
            'description' => strip_tags($this->siteInfo['description'] ?? ''),
            'url' => url('/'),
            'inLanguage' => str_replace('_', '-', App::getLocale()),
            'publisher' => $this->getPublisher(),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/search?q={search_term_string}')
                ],
                'query-input' => 'required name=search_term_string'
            ],
            'keywords' => $this->siteInfo['keyword'] ?? null
        ];
    }

    /**
     * 生成 NewsCollectionPage JSON-LD 結構化資料（新聞列表頁用）
     */
    public function generateNewsCollectionPage($articles, array $pageInfo = [], array $metaOverride = []): array
    {
        $itemListElement = [];
        $position = 1;

        // 將每篇文章轉換為 ListItem 包含 NewsArticle
        foreach ($articles as $article) {
            // 轉換成陣列（如果是物件的話）
            if (is_object($article)) {
                $article = (array) $article;
            }

            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => [
                    '@type' => 'NewsArticle',
                    '@id' => route('articles.show', $article['id']),
                    'headline' => $this->generateHeadline(
                        is_array($article['title']) ?
                            ($article['title'][App::getLocale()] ?? $article['title']['zh_TW'] ?? '') :
                            $article['title']
                    ),
                    'url' => route('articles.show', $article['id']),
                    'datePublished' => $this->formatDateForJsonLd($article['jsonld_publish_date'] ?? $article['created_at'] ?? null),
                    'dateModified' => $this->formatDateForJsonLd($article['updated_at'] ?? $article['created_at'] ?? null),
                    'image' => isset($article['jsonld_image']) && $article['jsonld_image'] ?
                        [$article['jsonld_image']] :
                        [asset('frontend/images/default.webp')],
                    'author' => $this->generateArticleAuthor($article)
                ]
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => ($pageInfo['name'] ?? '最新新聞') . ' - ' . ($this->siteInfo['title'] ?? 'SJTV'),
            'description' => $metaOverride['description'] ?? $this->siteInfo['description'] ?? null,
            'url' => $pageInfo['url'] ?? url()->current(),
            'inLanguage' => str_replace('_', '-', App::getLocale()),
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => url('/')
            ],
            'publisher' => $this->getPublisher(),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $itemListElement,
                'numberOfItems' => count($itemListElement)
            ]
        ];
    }

    /**
     * 生成 WebPage JSON-LD 結構化資料（靜態頁面用）
     */
    public function generateWebPage(array $pageInfo): array
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $pageInfo['name'] ?? '',
            'url' => $pageInfo['url'] ?? url()->current(),
            'inLanguage' => str_replace('_', '-', App::getLocale()),
            'isPartOf' => [
                '@type' => 'WebSite',
                '@id' => url('/')
            ]
        ];

        // 加入描述（如果有提供）
        if (!empty($pageInfo['description'])) {
            $jsonLd['description'] = strip_tags($pageInfo['description']);
        }

        // 加入 mainEntity（如果有提供）
        if (!empty($pageInfo['mainEntity'])) {
            $jsonLd['mainEntity'] = [
                '@type' => 'Organization',
                'name' => $pageInfo['mainEntity']['name'] ?? 'SJTV',
                'url' => url('/')
            ];
        }

        // 加入發布者資訊
        $jsonLd['publisher'] = $this->getPublisher();

        return $jsonLd;
    }

    /**
     * 根據內容類型自動生成對應的 JSON-LD
     */
    public function generateByType(string $type, array $data): string
    {
        switch ($type) {
            case 'article':
                return $this->toJsonLd($this->generateNewsArticle($data));

            case 'news':
                return $this->toJsonLd($this->generateNews($data));

            case 'drama':
                return $this->toJsonLd($this->generateTVSeries($data));

            case 'program':
                return $this->toJsonLd($this->generateTVSeason($data));

            case 'media-content':
                // 統一處理影音和節目，$data 應該包含 'type' 和 'data' 兩個鍵
                if (!isset($data['type']) || !isset($data['data'])) {
                    return '';
                }
                return $this->toJsonLd($this->generateMediaContent($data['type'], $data['data']));

            case 'video-object':
                // 處理單集影片，$data 應該包含 'content'、'episode' 和 'type' 三個鍵
                if (!isset($data['content']) || !isset($data['episode'])) {
                    return '';
                }
                return $this->toJsonLd($this->generateVideoObject(
                    $data['content'],
                    $data['episode'],
                    $data['type'] ?? 'drama'
                ));

            case 'live':
                return $this->toJsonLd($this->generateBroadcastEvent($data));

            case 'radio':
                return $this->toJsonLd($this->generateRadioSeries($data));

            case 'website':
                return $this->toJsonLd($this->generateWebSite());

            case 'webpage':
                return $this->toJsonLd($this->generateWebPage($data));

            case 'news-collection':
                // $data 應該包含 'articles' 和 'pageInfo' 兩個鍵
                return $this->toJsonLd($this->generateNewsCollectionPage(
                    $data['articles'] ?? [],
                    $data['pageInfo'] ?? []
                ));

            case 'collection-page':
                // $data 應該包含 'type'、'items'、'pageInfo' 等資訊
                return $this->toJsonLd($this->generateCollectionPage(
                    $data['type'] ?? 'drama',
                    $data['items'] ?? [],
                    $data['pageInfo'] ?? []
                ));

            case 'contact-page':
                // $data 應該包含聯絡資訊
                return $this->toJsonLd($this->generateContactPage($data));

            default:
                return '';
        }
    }

    /**
     * 生成 CollectionPage JSON-LD 結構化資料（篩選頁面用）
     */
    public function generateCollectionPage(string $contentType, array $items = [], array $pageInfo = []): array
    {
        $locale = App::getLocale();

        // 根據內容類型設定標題和描述
        $typeNames = [
            'drama' => __('frontend.nav.drama'),
            'program' => __('frontend.nav.program'),
            'radio' => __('frontend.nav.radio')
        ];
        $typeName = $typeNames[$contentType] ?? $contentType;

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $typeName . __('frontend.filter.filter_title'),
            'description' => __('frontend.filter.collection_description', ['type' => $typeName]),
            'url' => url()->current(),
            'inLanguage' => str_replace('_', '-', $locale),
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => 0, // 將在後面更新
                'itemListElement' => []
            ]
        ];

        // 處理代表性內容項目（前3筆）
        if (!empty($items)) {
            $itemListElements = [];

            foreach (array_slice($items, 0, 3) as $position => $item) {
                // 設定不同內容類型的 Schema.org 類型和 URL
                $schemaTypes = [
                    'drama' => 'TVSeries',
                    'program' => 'TVSeason',
                    'radio' => 'RadioSeries'
                ];
                $schemaType = $schemaTypes[$contentType] ?? 'CreativeWork';

                // 設定 URL 格式
                $itemUrl = $contentType === 'radio'
                    ? route('radio.show', $item['id'])
                    : route($contentType . '.videos.index', $item['id']);

                $itemElement = [
                    '@type' => 'ListItem',
                    'position' => $position + 1,
                    'item' => [
                        '@type' => $schemaType,
                        'name' => is_array($item['title'])
                            ? ($item['title'][$locale] ?? $item['title']['zh_TW'] ?? '')
                            : $item['title'],
                        'url' => $itemUrl
                    ]
                ];

                // 加入描述（根據內容類型處理不同欄位）
                if ($contentType === 'radio') {
                    // 廣播：優先使用 media_name，備援 description
                    $description = !empty($item['media_name']) ? $item['media_name'] : ($item['description'] ?? '');
                } else {
                    // 影音/節目：使用 description
                    $description = !empty($item['description'])
                        ? (is_array($item['description']) ? ($item['description'][$locale] ?? $item['description']['zh_TW'] ?? '') : $item['description'])
                        : '';
                }
                if (!empty($description)) {
                    $itemElement['item']['description'] = strip_tags($description);
                }

                // 加入圖片（根據內容類型處理不同欄位）
                if ($contentType === 'radio') {
                    // 廣播：使用 image
                    if (!empty($item['image'])) {
                        $itemElement['item']['image'] = asset($item['image']);
                    }
                } else {
                    // 影音/節目：使用 poster_desktop
                    if (!empty($item['poster_desktop'])) {
                        $itemElement['item']['image'] = asset($item['poster_desktop']);
                    }
                }

                // 加入發布年份（如果有）
                if (!empty($item['release_year'])) {
                    $itemElement['item']['datePublished'] = $item['release_year'] . '-01-01';
                }

                $itemListElements[] = $itemElement;
            }

            $jsonLd['mainEntity']['numberOfItems'] = count($items);
            $jsonLd['mainEntity']['itemListElement'] = $itemListElements;
        }

        return $jsonLd;
    }

    /**
     * 生成 ContactPage JSON-LD 結構化資料（客服中心頁面用）
     */
    public function generateContactPage(array $data = []): array
    {
        $locale = App::getLocale();

        // 基本的 ContactPage 結構
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => __('frontend.customer_service.title'),
            'description' => __('frontend.customer_service.description'),
            'url' => url()->current(),
            'inLanguage' => str_replace('_', '-', $locale),
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => __('frontend.nav.home'),
                        'item' => url('/')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => __('frontend.customer_service.title'),
                        'item' => url()->current()
                    ]
                ]
            ],
            'mainEntity' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'SJTV'),
                'url' => url('/'),
                'logo' => asset('frontend/images/logo.svg'),
                'contactPoint' => [
                    [
                        '@type' => 'ContactPoint',
                        'telephone' => isset($data['tel']) ? '+886-' . ltrim($data['tel'], '0') : '+886-5-3701199',
                        'contactType' => 'customer service',
                        'areaServed' => 'TW',
                        'availableLanguage' => ['zh-TW', 'en'],
                        'email' => $data['email'] ?? 'sjtvonline@gmail.com'
                    ]
                ]
            ],
            'publisher' => $this->getPublisher(),
            'datePublished' => $this->formatDateForJsonLd($data['created_at'] ?? now()),
            'dateModified' => $this->formatDateForJsonLd($data['updated_at'] ?? now())
        ];

        return $jsonLd;
    }
}