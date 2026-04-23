<?php

namespace Modules\EcpayPayment\Console\Commands;

use Illuminate\Console\Command;
use Modules\EcpayPayment\Backend\Service\EcpayInvoiceService;

/**
 * 開立待處理發票排程
 * 
 * 用法：php artisan ecpay:issue-invoices
 * 排程：每 5 分鐘執行一次
 */
class IssueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ecpay:issue-invoices 
                            {--limit=50 : 每次處理的發票數量}';

    /**
     * The console command description.
     */
    protected $description = '開立待處理的綠界電子發票';

    /**
     * Execute the console command.
     */
    public function handle(EcpayInvoiceService $invoiceService): int
    {
        $limit = (int) $this->option('limit');

        $this->info("開始處理待開立發票（上限：{$limit} 筆）...");

        $results = $invoiceService->issuePendingInvoices($limit);

        $this->info("處理完成！");
        $this->table(
            ['總數', '成功', '失敗'],
            [[$results['total'], $results['success'], $results['failed']]]
        );

        // 顯示詳細結果
        if (!empty($results['details'])) {
            $this->newLine();
            $this->info('詳細結果：');

            foreach ($results['details'] as $detail) {
                $status = $detail['success'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $message = $detail['invoice_no'] ?? $detail['rtn_msg'] ?? $detail['error'] ?? '';
                
                $this->line(
                    "  {$status} Invoice #{$detail['invoice_id']} (Payment #{$detail['payment_id']}): {$message}"
                );
            }
        }

        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
