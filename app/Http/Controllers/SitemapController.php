<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Sitemap 控制器
 *
 * 負責提供靜態 Sitemap XML 檔案的存取
 * 檔案由 php artisan sitemap:generate 指令生成
 * 存放於 storage/app/public/sitemaps/
 */
class SitemapController extends Controller
{
    /**
     * Sitemap 存放目錄
     */
    protected string $sitemapPath = 'sitemaps';

    /**
     * Sitemap Index - 所有子 Sitemap 的索引
     *
     * @return Response|SymfonyResponse
     */
    public function index(): Response|SymfonyResponse
    {
        return $this->serveSitemap('sitemap.xml');
    }

    /**
     * 動態提供任意 Sitemap 檔案
     *
     * 支援格式：
     * - sitemap-static.xml
     * - sitemap-news.xml
     * - sitemap-articles-zh-1.xml
     * - sitemap-articles-en-1.xml
     * - sitemap-dramas-zh.xml
     * - sitemap-programs-en.xml
     * - 等等...
     *
     * @param string $filename
     * @return Response|SymfonyResponse
     */
    public function show(string $filename): Response|SymfonyResponse
    {
        // 安全檢查：只允許 .xml 副檔名
        if (!str_ends_with($filename, '.xml')) {
            abort(404);
        }

        // 安全檢查：防止目錄遍歷攻擊
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(404);
        }

        return $this->serveSitemap($filename);
    }

    /**
     * 提供 Sitemap 檔案
     *
     * @param string $filename
     * @return Response|SymfonyResponse
     */
    protected function serveSitemap(string $filename): Response|SymfonyResponse
    {
        $filePath = "{$this->sitemapPath}/{$filename}";

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Sitemap not found');
        }

        $content = Storage::disk('public')->get($filePath);
        $lastModified = Storage::disk('public')->lastModified($filePath);

        return response($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
        ]);
    }
}