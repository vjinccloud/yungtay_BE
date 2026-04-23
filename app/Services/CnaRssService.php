<?php

namespace App\Services;

use App\Repositories\ArticleRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\ImageRepository;
use App\Services\CnaTagCrawlerService;
use App\Services\CnaImageCrawlerService;
use App\Traits\HttpClientTrait;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use SimpleXMLElement;
use Exception;

/**
 * CNA RSS 整合服務
 * 處理 CNA RSS Feed 的抓取、解析和匯入
 */
class CnaRssService
{
    use HttpClientTrait;
    /**
     * 來源提供者識別碼
     */
    private const PROVIDER = 'cna';

    /**
     * 預設分類 ID（當無法對應分類時使用）
     */
    private const DEFAULT_CATEGORY_ID = 1;


    /**
     * 要測試的文章 ID 清單
     */
    private array $debugArticleIds = [];

    /**
     * 建構函式
     */
    public function __construct(
        private ArticleRepository $articleRepository,
        private CategoryRepository $categoryRepository,
        private ImageRepository $imgRepository,
        private CnaTagCrawlerService $tagCrawler,
        private CnaImageCrawlerService $imageCrawler
    ) {
        // 確保日誌目錄存在
        $this->ensureLogDirectoryExists();
    }

    /**
     * 同步 RSS Feed 資料
     *
     * @param array $options 選項設定
     * @return array 執行結果
     */
    public function syncFromFeed(array $options = []): array
    {
        try {
            // 記錄開始時間
            $startTime = microtime(true);

            // 使用傳入的 feed_url 或預設值
            $feedUrl = $options['feed_url'] ?? config('cna.feeds.' . config('cna.default_feed'));
            $limit = $options['limit'] ?? null;
            $force = $options['force'] ?? false;
            $verbose = $options['verbose'] ?? false;
            $output = $options['output'] ?? null;
            $this->debugArticleIds = $options['debug_ids'] ?? [];

            // 記錄同步開始
            Log::channel('cna-rss')->info('CNA RSS 同步開始', [
                'feed_url' => $feedUrl,
                'limit' => $limit,
                'force' => $force,
                'timestamp' => now()->toDateTimeString()
            ]);

            // 1. 抓取 RSS Feed
            $feedData = $this->fetchFeed($feedUrl);

            if (!$feedData) {
                throw new Exception('無法抓取 RSS Feed 資料');
            }

            // 2. 解析 RSS XML
            $items = $this->parseFeed($feedData);

            if (empty($items)) {
                Log::channel('cna-rss')->warning('RSS Feed 沒有找到任何項目');
                return [
                    'status' => true,
                    'message' => 'RSS Feed 沒有找到任何項目',
                    'processed' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => 0
                ];
            }

            // 如果有限制數量，只處理部分項目
            if ($limit && is_numeric($limit) && $limit > 0) {
                $items = array_slice($items, 0, (int)$limit);
            }

            // 3. 匯入文章
            $results = $this->importArticles($items, $force, $verbose, $output);

            // 計算執行時間
            $executionTime = round(microtime(true) - $startTime, 2);

            // 記錄同步完成
            Log::channel('cna-rss')->info("========================================");
            Log::channel('cna-rss')->info("CNA RSS 同步完成");
            Log::channel('cna-rss')->info("處理文章數: " . $results['total']);
            Log::channel('cna-rss')->info("新增文章數: " . $results['imported']);
            Log::channel('cna-rss')->info("更新文章數: " . $results['updated']);
            Log::channel('cna-rss')->info("略過文章數: " . $results['skipped']);
            Log::channel('cna-rss')->info("錯誤數量: " . $results['errors']);
            Log::channel('cna-rss')->info("執行時間: {$executionTime} 秒");
            Log::channel('cna-rss')->info("========================================");

            // 將結果轉換為 Command 期望的格式
            return [
                'status' => true,
                'message' => $results['message'],
                'processed' => $results['total'],
                'created' => $results['imported'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors' => $results['errors'],
            ];

        } catch (Exception $e) {
            // 分析錯誤類型以提供更好的錯誤訊息
            $errorType = '未知錯誤';
            $suggestion = '';

            if (str_contains($e->getMessage(), 'cURL error 28') || str_contains($e->getMessage(), 'timeout')) {
                $errorType = '網路超時';
                $suggestion = '建議檢查網路連線或增加超時時間設定';
            } elseif (str_contains($e->getMessage(), 'RSS Feed')) {
                $errorType = 'RSS Feed 抓取失敗';
                $suggestion = '請檢查 RSS Feed URL 是否正常或稍後再試';
            } elseif (str_contains($e->getMessage(), 'XML')) {
                $errorType = 'XML 解析錯誤';
                $suggestion = '請檢查 RSS Feed 格式是否正確';
            }

            Log::channel('cna-rss')->error('CNA RSS 同步失敗', [
                'error_type' => $errorType,
                'error' => $e->getMessage(),
                'suggestion' => $suggestion,
                'feed_url' => $feedUrl ?? 'unknown',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => false,
                'message' => "RSS 同步失敗 ({$errorType}): " . $e->getMessage() . ($suggestion ? " - {$suggestion}" : ''),
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 1
            ];
        }
    }

    /**
     * 抓取 RSS Feed 資料
     *
     * @return string|null RSS XML 內容
     */
    private function fetchFeed(string $feedUrl): ?string
    {
        $maxRetries = config('cna.sync.max_retries', 3);
        $retryDelay = config('cna.sync.retry_delay', 5);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::channel('cna-rss')->info("RSS Feed 抓取嘗試 {$attempt}/{$maxRetries}", [
                    'url' => $feedUrl,
                    'attempt' => $attempt
                ]);

                // 使用共用的 HTTP 客戶端
                $response = $this->createRssHttpClient()->get($feedUrl);

                if ($response->successful()) {
                    Log::channel('cna-rss')->info('RSS Feed 抓取成功', [
                        'attempt' => $attempt,
                        'content_length' => strlen($response->body())
                    ]);
                    return $response->body();
                }

                Log::channel('cna-rss')->warning("RSS Feed 抓取失敗 (嘗試 {$attempt}/{$maxRetries})", [
                    'status' => $response->status(),
                    'reason' => $response->reason(),
                    'body_preview' => substr($response->body(), 0, 200)
                ]);

            } catch (Exception $e) {
                Log::channel('cna-rss')->warning("RSS Feed 抓取異常 (嘗試 {$attempt}/{$maxRetries})", [
                    'error' => $e->getMessage(),
                    'url' => $feedUrl,
                    'attempt' => $attempt
                ]);

                // 如果是最後一次嘗試，記錄最終錯誤
                if ($attempt === $maxRetries) {
                    Log::channel('cna-rss')->error('RSS Feed 抓取最終失敗', [
                        'error' => $e->getMessage(),
                        'url' => $feedUrl,
                        'total_attempts' => $maxRetries
                    ]);
                }
            }

            // 如果不是最後一次嘗試，等待後重試
            if ($attempt < $maxRetries) {
                Log::channel('cna-rss')->info("等待 {$retryDelay} 秒後重試...");
                sleep($retryDelay);
            }
        }

        return null;
    }

    /**
     * 解析 RSS Feed XML
     *
     * @param string $feedData RSS XML 資料
     * @return array 解析後的文章陣列
     */
    private function parseFeed(string $feedData): array
    {
        try {
            // PHP 8+ 使用 LIBXML 旗標防止 XXE 攻擊（替代已廢棄的 libxml_disable_entity_loader）
            $xml = simplexml_load_string(
                $feedData,
                SimpleXMLElement::class,
                LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
            );

            if ($xml === false) {
                throw new Exception('XML 格式無效或解析失敗');
            }

            $items = [];

            // 檢查是否有 channel 和 item
            if (!isset($xml->channel) || !isset($xml->channel->item)) {
                Log::channel('cna-rss')->warning('RSS Feed 缺少 channel 或 item 元素');
                return [];
            }

            foreach ($xml->channel->item as $item) {
                $parsedItem = $this->parseRssItem($item);
                if ($parsedItem) {
                    // 保存原始 XML 以供後續使用
                    $parsedItem['_original_xml_item'] = $item;
                    $items[] = $parsedItem;
                }
            }

            return $items;

        } catch (Exception $e) {
            Log::channel('cna-rss')->error('RSS XML 解析失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * 解析單一 RSS 項目
     *
     * @param SimpleXMLElement $item RSS 項目
     * @return array|null 解析後的文章資料
     */
    private function parseRssItem(SimpleXMLElement $item): ?array
    {
        try {
            // 基本欄位
            $title = trim((string) $item->title);
            $link = trim((string) $item->link);
            $description = trim((string) $item->description);
            $guid = trim((string) $item->guid);

            if (empty($title) || empty($guid)) {
                Log::channel('cna-rss')->warning('RSS 項目缺少必要欄位', [
                    'title' => $title,
                    'guid' => $guid
                ]);
                return null;
            }

            // 發布時間
            $pubDate = null;
            if (isset($item->pubDate)) {
                $pubDate = $this->parseDate((string) $item->pubDate);
            }

            // CNA 特有欄位
            $dcModified = null;
            if (isset($item->children('http://purl.org/dc/elements/1.1/')->modified)) {
                $dcModified = $this->parseDate((string) $item->children('http://purl.org/dc/elements/1.1/')->modified);
            }

            $commentsCount = 0;
            if (isset($item->comments)) {
                $commentsCount = (int) $item->comments;
            }

            // 分類代碼
            $categoryCode = null;
            if (isset($item->category)) {
                $categoryCode = trim((string) $item->category);
            }

            // 圖片處理（僅用於日誌記錄，實際處理使用 CnaImageCrawlerService）
            $images = [];

            // 內容處理（先不處理圖片，等到真正要匯入時才處理）
            $content = $this->parseContent($item, null);

            // 解析地點資訊（從 description 中提取）
            $location = $this->parseLocation($description);

            $parsedData = [
                'title' => $title,
                'link' => $link,
                'description' => $description,
                'guid' => $guid,
                'guid_hash' => hash('sha256', $guid),
                'pub_date' => $pubDate,
                'dc_modified' => $dcModified,
                'comments_count' => $commentsCount,
                'category_code' => $categoryCode,
                'images' => $images,
                'content' => $content,
                'author' => $this->parseAuthor($item),
                'location' => $location,
                '_original_xml_item' => $item, // 保存原始 XML 項目以便後續處理
            ];


            return $parsedData;

        } catch (Exception $e) {
            Log::channel('cna-rss')->error('RSS 項目解析失敗', [
                'error' => $e->getMessage(),
                'item_title' => isset($item->title) ? (string) $item->title : 'unknown'
            ]);
            return null;
        }
    }

    /**
     * 解析日期時間
     *
     * @param string $dateString 日期字串
     * @return Carbon|null
     */
    private function parseDate(string $dateString): ?Carbon
    {
        try {
            return Carbon::parse($dateString);
        } catch (Exception $e) {
            Log::channel('cna-rss')->warning('日期解析失敗', [
                'date_string' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }



    /**
     * 解析 media:content 的圖片資訊
     *
     * @param SimpleXMLElement $item RSS 項目
     * @return array 解析後的照片資料陣列
     */
    private function parseMediaContent($item): array
    {
        $mediaContents = [];

        try {
            // 取得 media 命名空間
            $mediaNamespace = 'http://search.yahoo.com/mrss/';
            $media = $item->children($mediaNamespace);

            if (isset($media->content)) {
                foreach ($media->content as $content) {
                    $attributes = $content->attributes();

                    // 只處理圖片類型
                    if ((string) $attributes['medium'] === 'image') {
                        $mediaData = [
                            'url' => (string) $attributes['url'],
                            'title' => isset($content->title) ? trim((string) $content->title) : '',
                            'description' => isset($content->description) ? trim((string) $content->description) : '',
                        ];

                        $mediaContents[] = $mediaData;
                    }
                }
            }

        } catch (Exception $e) {
            // 錯誤處理
        }

        return $mediaContents;
    }

    /**
     * 將 media:content 轉換成 HTML
     *
     * @param array $mediaContents 照片資料陣列
     * @param string|null $newsUrl 新聞頁面 URL
     * @return string HTML 格式的圖片
     */
    private function convertMediaToHtml(array $mediaContents, ?string $newsUrl = null): string
    {
        if (empty($mediaContents) || !$newsUrl) {
            return '';
        }

        // 步驟 1: 收集所有圖片 ID
        $imageIds = [];
        $mediaMap = []; // 用來記錄 ID 與 media 資料的對應

        foreach ($mediaContents as $media) {
            // 解析路徑格式：photo/YYYYMMDD/YYYYMMDDHHMMSS
            if (preg_match('/photo\/\d{8}\/(\d{14})/', $media['url'], $matches)) {
                $imageId = $matches[1];
                $imageIds[] = $imageId;
                $mediaMap[$imageId] = $media;
            }
        }

        if (empty($imageIds)) {
            Log::channel('cna-rss')->warning('CNA RSS: 無法解析任何圖片 ID', [
                'media_contents' => $mediaContents
            ]);
            return '';
        }
        // 步驟 2: 批次爬取取得所有圖片 URL
        $imageUrls = $this->imageCrawler->getCnaImageUrlsByIds($imageIds, $newsUrl);

        // 步驟 3: 轉換成 HTML
        $html = '';
        foreach ($imageIds as $imageId) {
            if (isset($imageUrls[$imageId])) {
                $media = $mediaMap[$imageId];
                $imageUrl = $imageUrls[$imageId];

                $html .= '<figure class="article-image">' . "\n";
                $html .= '    <img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '"' . "\n";
                $html .= '         alt="' . htmlspecialchars($media['title'], ENT_QUOTES, 'UTF-8') . '"' . "\n";
                $html .= '         loading="lazy">' . "\n";
                $html .= '    <figcaption>' . htmlspecialchars($media['description'], ENT_QUOTES, 'UTF-8') . '</figcaption>' . "\n";
                $html .= '</figure>' . "\n\n";
            } else {
                // 記錄找不到的圖片
                Log::channel('cna-rss')->error('CNA RSS: 找不到圖片 URL', [
                    'image_id' => $imageId,
                    'news_url' => $newsUrl,
                    'original_path' => $mediaMap[$imageId]['url']
                ]);
            }
        }

        return $html;
    }

    /**
     * 解析內容
     *
     * @param SimpleXMLElement $item RSS 項目
     * @param string|null $newsUrl 新聞頁面 URL
     * @return string
     */
    private function parseContent(SimpleXMLElement $item, ?string $newsUrl = null): string
    {
        $content = '';
        $contentNamespace = $item->children('http://purl.org/rss/1.0/modules/content/');
        if (isset($contentNamespace->encoded)) {
            $content = trim((string) $contentNamespace->encoded);
        }


        // 解析並加入照片（傳遞新聞 URL 用於爬取）
        $mediaContents = $this->parseMediaContent($item);
        if (!empty($mediaContents)) {
            $mediaHtml = $this->convertMediaToHtml($mediaContents, $newsUrl);
            $content .= "\n\n" . $mediaHtml;
        }

        return $content;
    }


    /**
     * 解析作者
     *
     * @param SimpleXMLElement $item RSS 項目
     * @return string
     */
    private function parseAuthor(SimpleXMLElement $item): string
    {
        // CNA 的文章通常沒有特定作者欄位，預設使用「中央社」
        if (isset($item->author) && !empty(trim((string) $item->author))) {
            return trim((string) $item->author);
        }

        return '中央社';
    }

    /**
     * 取得城市列表的正則表達式字串
     *
     * @return string
     */
    private function getCitiesRegexString(): string
    {
        return implode('|', config('cna.cities', []));
    }

    /**
     * 解析地點資訊
     * 從 CNA 的 description 中提取地點資訊
     * 支援多種 CNA 新聞格式並提供高準確率的地點識別
     *
     * @param string $description 新聞描述文字
     * @return string 解析出的地點名稱，失敗時返回空字串
     */
    private function parseLocation(string $description): string
    {
        if (empty($description)) {
            return '';
        }

        try {
            // 解碼 HTML 實體
            $cleanDesc = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // 使用靜態快取避免重複建構正則表達式
            static $citiesRegex = null;
            if ($citiesRegex === null) {
                $citiesRegex = $this->buildOptimizedCitiesRegex();
            }

            // 定義匹配模式（按準確度和常見度排序）
            $patterns = [
                // 高準確度模式：含有明確地點的標準格式
                [
                    'pattern' => "/中央社記者.*?({$citiesRegex})\d+日(?:電|專電)/u",
                    'priority' => 10,
                    'description' => '記者地點格式'
                ],
                [
                    'pattern' => "/（中央社記者[^）]*?({$citiesRegex})\d+日(?:電|專電)）/u",
                    'priority' => 10,
                    'description' => '括號記者地點格式'
                ],
                [
                    'pattern' => "/中央社({$citiesRegex})\d+日綜合外電報導/u",
                    'priority' => 9,
                    'description' => '綜合外電格式'
                ],
                [
                    'pattern' => "/（中央社({$citiesRegex})\d+日綜合外電報導）/u",
                    'priority' => 9,
                    'description' => '括號綜合外電格式'
                ],
                // 中準確度模式：需要進一步處理的格式
                [
                    'pattern' => "/（中央社([^）]+?)\d+日(?:綜合外電|電|專電|綜合報導)?）/u",
                    'priority' => 7,
                    'description' => '通用括號格式',
                    'process' => true
                ],
                [
                    'pattern' => "/中央社([^）\d]{2,10})\d+日(?:電|專電)/u",
                    'priority' => 6,
                    'description' => '簡短地點格式',
                    'process' => true
                ],
                // 備用模式：通用格式
                [
                    'pattern' => "/（中央社([^）]+?)報導）/u",
                    'priority' => 5,
                    'description' => '報導格式',
                    'process' => true
                ]
            ];

            // 按優先順序嘗試匹配
            foreach ($patterns as $patternInfo) {
                if (preg_match($patternInfo['pattern'], $cleanDesc, $matches)) {
                    $location = $this->processLocationMatch($matches[1], $patternInfo, $citiesRegex);

                    if (!empty($location)) {
                        return $location;
                    }
                }
            }

            // 如果標準格式都無法匹配，嘗試智能提取
            $smartLocation = $this->smartLocationExtraction($cleanDesc, $citiesRegex);
            if (!empty($smartLocation)) {
                return $smartLocation;
            }

            return '';

        } catch (Exception $e) {
            Log::channel('cna-rss')->warning('地點解析異常', [
                'error' => $e->getMessage(),
                'description_preview' => mb_substr($description, 0, 100)
            ]);
            return '';
        }
    }

    /**
     * 建構優化的城市正則表達式（靜態快取）
     *
     * @return string
     */
    private function buildOptimizedCitiesRegex(): string
    {
        // 按字符長度排序（長的優先），避免匹配衝突
        $cities = config('cna.cities', []);
        usort($cities, function($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });

        // 轉義特殊字符並連接
        $escapedCities = array_map(function($city) {
            return preg_quote($city, '/');
        }, $cities);

        return implode('|', $escapedCities);
    }

    /**
     * 處理地點匹配結果
     *
     * @param string $rawLocation 原始匹配的地點字串
     * @param array $patternInfo 模式資訊
     * @param string $citiesRegex 城市正則表達式
     * @return string 處理後的地點名稱
     */
    private function processLocationMatch(string $rawLocation, array $patternInfo, string $citiesRegex): string
    {
        // 高優先度模式直接返回
        if ($patternInfo['priority'] >= 9) {
            return trim($rawLocation);
        }

        // 需要進一步處理的模式
        if (isset($patternInfo['process']) && $patternInfo['process']) {
            return $this->cleanLocationString($rawLocation, $citiesRegex);
        }

        return trim($rawLocation);
    }

    /**
     * 清理地點字串
     *
     * @param string $locationString 原始地點字串
     * @param string $citiesRegex 城市正則表達式
     * @return string 清理後的地點名稱
     */
    private function cleanLocationString(string $locationString, string $citiesRegex): string
    {
        // 移除常見的干擾詞彙
        $cleanPatterns = [
            '/^記者[^）]*/',                           // 移除開頭記者名
            '/記者[^）]*$/',                           // 移除結尾記者名
            '/\d+日(?:綜合外電|電|專電|綜合報導)?$/',      // 移除日期部分
            '/^[\s\(\)（）]*/',                        // 移除開頭空白和括號
            '/[\s\(\)（）]*$/',                        // 移除結尾空白和括號
        ];

        $cleaned = $locationString;
        foreach ($cleanPatterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }

        $cleaned = trim($cleaned);

        // 確保是合法 UTF-8，避免出現「仰�」這種情況
        if (!mb_check_encoding($cleaned, 'UTF-8') || strpos($cleaned, '�') !== false) {
            return '';
        }

        // 優先匹配已知城市
        if (preg_match("/({$citiesRegex})/u", $cleaned, $cityMatch)) {
            return $cityMatch[1];
        }

        // 長度和字符檢查
        if (!empty($cleaned) &&
            mb_strlen($cleaned) >= 2 &&
            mb_strlen($cleaned) <= 15 &&
            !preg_match('/[0-9\[\]【】]/', $cleaned)) {
            return $cleaned;
        }

        return '';
    }

    /**
     * 智能地點提取（備用方案）
     *
     * @param string $text 文本內容
     * @param string $citiesRegex 城市正則表達式
     * @return string 提取的地點名稱
     */
    private function smartLocationExtraction(string $text, string $citiesRegex): string
    {
        // 在整個文本中尋找已知城市名
        if (preg_match_all("/({$citiesRegex})/u", $text, $matches)) {
            // 返回第一個匹配的城市（通常是最相關的）
            return $matches[1][0];
        }

        // 尋找可能的地點關鍵詞模式
        $locationPatterns = [
            '/([^\s]{2,8}(?:市|縣|區|鎮|鄉|島|省|州|都))(?:\d+日|報導|電|消息)/',
            '/(?:位於|在|於)([^\s]{2,10}(?:市|縣|區|鎮))/',
            '/([A-Za-z\s]{3,20})(?:\d+日|報導|電)/',  // 英文地名
        ];

        foreach ($locationPatterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                $candidate = trim($match[1]);
                if (mb_strlen($candidate) >= 2 && mb_strlen($candidate) <= 12) {
                    return $candidate;
                }
            }
        }

        return '';
    }



    /**
     * 匯入文章資料
     *
     * @param array $items 解析後的文章陣列
     * @param bool $force 是否強制更新
     * @param bool $verbose 是否顯示詳細輸出
     * @param mixed $output 命令行輸出實例
     * @return array 匯入結果統計
     */
    private function importArticles(array $items, bool $force = false, bool $verbose = false, $output = null): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $total = count($items);
        $current = 0;

        foreach ($items as $item) {
            $current++;

            // 詳細輸出模式
            if ($verbose && $output) {
                $title = mb_substr($item['title'] ?? '無標題', 0, 50);
                $output->line("[{$current}/{$total}] 處理: {$title}...", null, 'info');
            }

            try {
                $result = $this->importArticle($item, $force);

                // 詳細輸出結果
                if ($verbose && $output) {
                    $status = match($result) {
                        'imported' => '✅ 新增',
                        'updated' => '🔄 更新',
                        'skipped' => '⏭️ 略過',
                        default => '❓ 未知'
                    };
                    $output->line("    結果: {$status}");
                }

                switch ($result) {
                    case 'imported':
                        $imported++;
                        break;
                    case 'updated':
                        $updated++;
                        break;
                    case 'skipped':
                        $skipped++;
                        break;
                }

            } catch (Exception $e) {
                // 詳細輸出錯誤
                if ($verbose && $output) {
                    $output->line("    結果: ❌ 錯誤");
                    $output->error("    錯誤訊息: " . $e->getMessage());
                }

                Log::channel('cna-rss')->error('文章匯入失敗', [
                    'title' => $item['title'],
                    'error' => $e->getMessage()
                ]);
                $errors++;
            }
        }

        return [
            'message' => sprintf('RSS 同步完成：新增 %d 篇，更新 %d 篇，跳過 %d 篇，錯誤 %d 篇', $imported, $updated, $skipped, $errors),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count($items)
        ];
    }

    /**
     * 匯入單一文章
     *
     * @param array $item 文章資料
     * @param bool $force 是否強制更新
     * @return string 匯入結果 ('imported', 'updated', 'skipped')
     */
    private function importArticle(array $item, bool $force = false): string
    {
        // 檢查文章是否已存在
        $existingArticle = $this->articleRepository->findByGuidHash(self::PROVIDER, $item['guid_hash']);

        // 判斷是否需要更新（強制更新時跳過檢查）
        if ($existingArticle && !$force && !$this->shouldUpdateArticle($existingArticle, $item)) {
            // 文章跳過時，不執行任何圖片相關處理
            return 'skipped';
        }

        // 檢查是否需要處理圖片
        // 條件：1. 新文章 或 2. RSS 中有圖片 (comments > 0) 就處理
        $shouldProcessImages = !$existingArticle ||
                               ($existingArticle && $item['comments_count'] > 0);

        // Debug: 檢查是否需要處理圖片
        $articleId = $existingArticle ? $existingArticle->id : null;

        // 如果需要處理圖片，重新解析內容以包含圖片
        if ($shouldProcessImages && !empty($item['link'])) {
            $articleId = $existingArticle ? $existingArticle->id : null;

            // 重新解析內容，這次包含圖片處理
            $contentWithImages = $this->parseContent($item['_original_xml_item'] ?? null, $item['link']);
            $item['content'] = $contentWithImages;
        }

        // 準備文章資料（只有需要匯入或更新時才準備）
        $articleData = $this->prepareArticleData($item);

        // 使用 CnaImageCrawlerService 抓取圖片
        $imageUrl = null;
        if ($shouldProcessImages && !empty($item['link'])) {
            try {
                // 先獲取 HTML 內容
                $response = $this->createCnaHttpClient()->get($item['link']);
                if ($response->successful()) {
                    $html = $response->body();
                    $images = $this->imageCrawler->extractImagesFromHtml($html);
                } else {
                    $images = [];
                }

                if (!empty($images)) {
                    // CnaImageCrawlerService 已經過濾掉 pic_fb.jpg，直接取第一張
                    $imageUrl = $images[0];
                }
            } catch (Exception $e) {
                Log::channel('cna-rss')->warning('CNA 圖片爬取失敗', [
                    'error' => $e->getMessage(),
                    'link' => $item['link']
                ]);
            }
        }

        // location 欄位現在始終保留，即使是空值也會有 JSON 結構
        // 這樣可以確保地點無法判定時也能正常寫入文章

        // 使用 ArticleRepository->save() 處理文章資料
        if ($existingArticle) {
            // 更新現有文章
            $article = $this->articleRepository->save($articleData, $existingArticle->id);
        } else {
            // 建立新文章（需要先設定 RSS 相關欄位）
            $articleData['source_provider'] = self::PROVIDER;
            $articleData['source_guid_hash'] = $item['guid_hash'];
            $article = $this->articleRepository->save($articleData);
        }

        // 使用 ImageRepository->saveRawImage() 處理圖片（享受壓縮+縮圖）
        if ($imageUrl && $this->imageCrawler->validateImageUrl($imageUrl)) {
            try {
                $imageResult = $this->imgRepository->saveRawImage(
                    $article,
                    $imageUrl,
                    'image_normal',    // 圖片類型
                    'articles',        // 路徑
                    true,             // 啟用壓縮（1600×900 + JPEG 92%）
                    true              // 啟用縮圖（443×250）
                );

                if (!$imageResult['success']) {
                    Log::channel('cna-rss')->warning('CNA 圖片處理失敗', [
                        'article_id' => $article->id,
                        'image_url' => $imageUrl,
                        'error' => $imageResult['error']
                    ]);
                }
            } catch (Exception $e) {
                Log::channel('cna-rss')->warning('CNA 圖片處理異常', [
                    'article_id' => $article->id,
                    'image_url' => $imageUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $existingArticle ? 'updated' : 'imported';
    }


    /**
     * 判斷是否需要更新文章
     *
     * @param \App\Models\Article $existingArticle
     * @param array $item
     * @return bool
     */
    private function shouldUpdateArticle($existingArticle, array $item): bool
    {
        // 測試特定文章的 XML
        $this->debugArticleXml($existingArticle->id, $existingArticle, $item);
        // 1. 檢查 source_modified_at 是否變新
        if ($item['dc_modified'] && $existingArticle->source_modified_at) {
            if ($item['dc_modified']->gt($existingArticle->source_modified_at)) {
                return true;
            }
        }

        // 2. 檢查 source_comments_count 是否有圖片（>0 就更新）
        if ($item['comments_count'] > $existingArticle->source_comments_count) {
            return true;
        }



        return false;
    }

    /**
     * 準備文章資料
     *
     * @param array $item RSS 項目資料
     * @return array 文章資料
     */
    private function prepareArticleData(array $item): array
    {
        // 對應分類
        $categoryId = $this->mapCategory($item['category_code']);


        // 多語系內容（暫時將中文內容同時寫入中文和英文欄位）
        $multiLangTitle = ['zh_TW' => $item['title'], 'en' => $item['title']];
        $multiLangContent = ['zh_TW' => $item['content'], 'en' => $item['content']];
        $multiLangAuthor = ['zh_TW' => $item['author'], 'en' => $item['author']];

        // 處理 location 欄位：如果無法判定地點，設為空字串而非 null
        $locationValue = !empty($item['location']) ? trim($item['location']) : '';

        // 始終設定多語言值，即使是空字串也要有 JSON 結構
        $multiLangLocation = ['zh_TW' => $locationValue, 'en' => $locationValue];

        // 爬取文章頁面的標籤（使用 CnaTagCrawlerService）
        $crawledTags = [];
        if (!empty($item['link'])) {
            try {
                $crawledTags = $this->tagCrawler->crawlTags($item['link']);
            } catch (Exception $e) {
                Log::channel('cna-rss')->warning('標籤爬取失敗', [
                    'link' => $item['link'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 生成標籤（基本標籤 + 爬取標籤）
        $tags = $this->generateTags($item, $crawledTags);
        $multiLangTags = ['zh_TW' => $tags, 'en' => $tags];

        return [
            'title' => $multiLangTitle,
            'content' => $multiLangContent,
            'author' => $multiLangAuthor,
            'location' => $multiLangLocation,
            'tags' => $multiLangTags,
            'category_id' => $categoryId,
            'publish_date' => $item['pub_date'] ? $item['pub_date']->toDateString() : now()->toDateString(),
            'is_active' => 1,
            'source_provider' => self::PROVIDER,
            'source_guid_hash' => $item['guid_hash'],
            'source_link' => $item['link'],
            'source_published_at' => $item['pub_date'],
            'source_modified_at' => $item['dc_modified'],
            'source_comments_count' => $item['comments_count'],
        ];
    }

    /**
     * 生成標籤（整合爬取的標籤和基本標籤）
     *
     * @param array $item RSS 項目資料
     * @param array $crawledTags 從文章頁面爬取的標籤
     * @return string 逗號分隔的標籤
     */
    private function generateTags(array $item, array $crawledTags = []): string
    {
        $tags = [];

        // 🔥 改變策略：先加基本標籤（保證一定有標籤），再加爬取標籤

        // 1. 加入 CNA 來源標籤（基本保證）
        $tags[] = '中央社';

        // 2. 使用分類名稱作為標籤（基本保證）
        $categoryMappings = config('cna.categories', []);
        if (!empty($item['category_code']) && isset($categoryMappings[$item['category_code']])) {
            $tags[] = $categoryMappings[$item['category_code']];
        }

        // 3. 如果有地點，加入地點標籤（基本保證）
        if (!empty($item['location'])) {
            $tags[] = $item['location'];
        }

        // 4. 额外添加爬取到的標籤（避免與基本標籤重複）
        foreach ($crawledTags as $crawledTag) {
            if (!$this->isTagDuplicate($crawledTag, $tags)) {
                $tags[] = $crawledTag;
            }
        }

        // 去除重複並用逗號連接
        $tags = array_unique($tags);
        return implode(',', $tags);
    }

    /**
     * 檢查標籤是否重複（模糊比對）
     *
     * @param string $newTag 新標籤
     * @param array $existingTags 現有標籤陣列
     * @return bool 是否重複
     */
    private function isTagDuplicate(string $newTag, array $existingTags): bool
    {
        $newTagCleaned = trim(strtolower($newTag));

        foreach ($existingTags as $existingTag) {
            $existingTagCleaned = trim(strtolower($existingTag));

            // 完全相同
            if ($newTagCleaned === $existingTagCleaned) {
                return true;
            }

            // 包含關係（避免 "台北" 和 "台北市" 重複）
            if (mb_strlen($newTagCleaned) > 2 && mb_strlen($existingTagCleaned) > 2) {
                if (strpos($newTagCleaned, $existingTagCleaned) !== false ||
                    strpos($existingTagCleaned, $newTagCleaned) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 對應分類
     *
     * @param string|null $categoryCode CNA 分類代碼
     * @return int 系統分類 ID
     */
    private function mapCategory(?string $categoryCode): int
    {
        if (empty($categoryCode)) {
            return self::DEFAULT_CATEGORY_ID;
        }

        // 使用 CategoryRepository 查找對應的分類 ID
        $categoryId = $this->categoryRepository->findIdBySourceCode($categoryCode, self::PROVIDER);

        return $categoryId ?: self::DEFAULT_CATEGORY_ID;
    }



    /**
     * 取得同步狀態資訊
     *
     * @return array
     */
    public function getSyncStatus(): array
    {
        $totalArticles = $this->articleRepository->getBySourceProvider(self::PROVIDER)->count();

        $latestArticle = $this->articleRepository->getBySourceProvider(self::PROVIDER)
                                               ->orderBy('source_modified_at', 'desc')
                                               ->first();

        return [
            'provider' => self::PROVIDER,
            'feed_url' => config('cna.feeds.' . config('cna.default_feed')),
            'total_articles' => $totalArticles,
            'latest_sync' => $latestArticle ? $latestArticle->source_modified_at : null,
            'latest_article_title' => $latestArticle ? $latestArticle->getTranslation('title', 'zh_TW') : null,
        ];
    }

    /**
     * Debug 特定文章的 XML 資料
     *
     * @param string $articleId 要檢查的文章 ID
     * @param mixed $existingArticle 現有文章
     * @param array $item RSS 項目資料
     */
    private function debugArticleXml($articleId, $existingArticle, array $item): void
    {
        // 使用外部傳入的 debug IDs 或空陣列
        if (!empty($this->debugArticleIds) && in_array($articleId, $this->debugArticleIds)) {
            Log::channel('cna-rss')->info('【測試】檢查文章 XML - ID: ' . $articleId, [
                'source_modified_at' => $existingArticle->source_modified_at,
                'dc_modified' => $item['dc_modified'],
                'original_xml' => isset($item['_original_xml_item']) ? $item['_original_xml_item']->asXML() : 'XML not available',
            ]);

            // 如果在命令行執行，也輸出到 console
            if (app()->runningInConsole()) {
                echo "\n[測試] 文章 ID {$articleId} 的 XML 已記錄到 log\n";
            }
        }
    }

    /**
     * 確保日誌目錄存在
     */
    private function ensureLogDirectoryExists(): void
    {
        $logDirectory = storage_path('logs/cna-rss');
        if (!file_exists($logDirectory)) {
            mkdir($logDirectory, 0755, true);
        }
    }
}
