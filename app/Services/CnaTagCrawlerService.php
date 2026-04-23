<?php

namespace App\Services;

use App\Traits\HttpClientTrait;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

/**
 * CNA 標籤爬蟲服務
 * 專責處理 CNA 新聞標籤的抓取
 */
class CnaTagCrawlerService
{
    use HttpClientTrait;
    /**
     * 抓取文章標籤
     *
     * @param string $articleUrl 文章 URL
     * @return array 標籤陣列
     */
    public function crawlTags(string $articleUrl): array
    {
        $tags = [];

        try {
            // 使用共用的 HTTP 客戶端
            $response = $this->createCnaHttpClient(30)->get($articleUrl);

            if (!$response->successful()) {
                Log::warning('CNA 標籤抓取失敗', [
                    'url' => $articleUrl,
                    'status' => $response->status()
                ]);
                return $tags;
            }

            $html = $response->body();
            $crawler = new Crawler($html);

            // 方法1: 從 JSON-LD 結構化資料抓取標籤（CNA 新格式）
            $jsonLdTags = $this->extractTagsFromJsonLd($crawler);
            if (!empty($jsonLdTags)) {
                $tags = array_merge($tags, $jsonLdTags);
                // 從 JSON-LD 抓取到標籤
            }

            // 方法2: 從 meta keywords 標籤抓取（傳統方法）
            try {
                $metaKeywords = $crawler->filter('meta[name="keywords"]')->attr('content');
                if (!empty($metaKeywords)) {
                    $keywordTags = array_map('trim', explode(',', $metaKeywords));
                    $keywordTags = array_filter($keywordTags); // 移除空值
                    $tags = array_merge($tags, $keywordTags);
                    // 從 meta keywords 抓取到標籤
                }
            } catch (Exception $e) {
                // meta keywords 抓取失敗（可能不存在）
            }

            // 方法3: 從文章標籤區塊抓取（傳統 HTML 標籤）
            try {
                $crawler->filter('.tag a, .tags a, .article-tags a')->each(function (Crawler $node) use (&$tags) {
                    $text = trim($node->text());
                    if (!empty($text) && !in_array($text, $tags)) {
                        $tags[] = $text;
                    }
                });
            } catch (Exception $e) {
                // HTML 標籤區塊抓取失敗（可能不存在）
            }

            // 方法4: 從相關標籤或關鍵字抓取
            try {
                $crawler->filter('.keywords, .related-keywords')->each(function (Crawler $node) use (&$tags) {
                    $keywords = trim($node->text());
                    if (!empty($keywords)) {
                        $keywordTags = array_map('trim', explode(',', $keywords));
                        $keywordTags = array_filter($keywordTags); // 移除空值
                        foreach ($keywordTags as $keywordTag) {
                            if (!in_array($keywordTag, $tags)) {
                                $tags[] = $keywordTag;
                            }
                        }
                    }
                });
            } catch (Exception $e) {
                // 關鍵字區塊抓取失敗（可能不存在）
            }

            // 方法5: 從文章分類抓取（最後備援）
            if (empty($tags)) {
                try {
                    $crawler->filter('.breadcrumb a')->each(function (Crawler $node) use (&$tags) {
                        $text = trim($node->text());
                        // 排除首頁等導航連結
                        if (!empty($text) && !in_array($text, ['首頁', '新聞', 'Home', '中央社 CNA'])) {
                            $tags[] = $text;
                        }
                    });
                } catch (Exception $e) {
                    // 麵包屑導航抓取失敗（可能不存在）
                }
            }

            // CNA 標籤抓取成功

        } catch (Exception $e) {
            Log::error('CNA 標籤抓取異常', [
                'url' => $articleUrl,
                'error' => $e->getMessage()
            ]);
        }

        return array_values(array_unique($tags));
    }

    /**
     * 從 JSON-LD 結構化資料中提取標籤
     *
     * @param Crawler $crawler DOM Crawler 實例
     * @return array 標籤陣列
     */
    private function extractTagsFromJsonLd(Crawler $crawler): array
    {
        $tags = [];

        try {
            // 查找所有 JSON-LD script 標籤
            $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$tags) {
                try {
                    $jsonContent = trim($node->text());
                    if (empty($jsonContent)) {
                        return;
                    }

                    $data = json_decode($jsonContent, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // JSON-LD 解析失敗
                        return;
                    }

                    // 處理單一物件或陣列格式
                    $jsonData = is_array($data) && isset($data[0]) ? $data : [$data];

                    foreach ($jsonData as $item) {
                        // 查找 NewsArticle 類型且包含 keywords 的資料
                        if (isset($item['@type']) && $item['@type'] === 'NewsArticle' && isset($item['keywords'])) {
                            if (is_array($item['keywords'])) {
                                // keywords 是陣列格式（新格式）
                                foreach ($item['keywords'] as $keyword) {
                                    if (is_string($keyword) && !empty(trim($keyword))) {
                                        $tags[] = trim($keyword);
                                    }
                                }
                            } elseif (is_string($item['keywords'])) {
                                // keywords 是字串格式（舊格式）
                                $keywordTags = array_map('trim', explode(',', $item['keywords']));
                                $keywordTags = array_filter($keywordTags);
                                $tags = array_merge($tags, $keywordTags);
                            }
                        }
                    }
                } catch (Exception $e) {
                    // JSON-LD 單一節點處理失敗
                }
            });
        } catch (Exception $e) {
            Log::warning('JSON-LD 標籤提取失敗', [
                'error' => $e->getMessage()
            ]);
        }

        return array_values(array_unique($tags));
    }

}
