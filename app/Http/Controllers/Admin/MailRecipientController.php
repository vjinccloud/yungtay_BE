<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MailRecipientRequest;
use App\Services\MailRecipientService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MailRecipientController extends Controller
{
    public function __construct(
        private MailRecipientService $mailRecipientService,
    ) {
    }
    /**
     * 顯示列表頁面
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'updated_at';
        $sortDirection = $request->input('sortDirection') ?? 'desc';
        $perPage = $request->input('length') ?? '10';

        $mailRecipients = $this->mailRecipientService->getMailRecipients($perPage, $sortColumn, $sortDirection, $filters);

        return Inertia::render('Admin/MailRecipient/Index', compact('mailRecipients'));
    }

    /**
     * 顯示新增表單
     */
    public function create()
    {
        return Inertia::render('Admin/MailRecipient/Form', [
            'mailTypes' => $this->mailRecipientService->getAllMailTypes()
        ]);
    }

    /**
     * 儲存新增的收件信箱
     */
    public function store(MailRecipientRequest $request)
    {
        $validated = $request->validated();
        $result = $this->mailRecipientService->save($validated);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 顯示單一收件信箱（重導向到編輯）
     */
    public function show($id)
    {
        return redirect()->route('admin.mail-recipients.edit', $id);
    }

    /**
     * 顯示編輯表單
     */
    public function edit(string $id)
    {
        $mailRecipient = $this->mailRecipientService->find($id);

        if (!$mailRecipient) {
            return redirect()->route('admin.mail-recipients')
                ->with('result', ['status' => false, 'msg' => '收件信箱不存在']);
        }

        return Inertia::render('Admin/MailRecipient/Form', [
            'mailRecipient' => $mailRecipient->load('mailType'),
            'mailTypes' => $this->mailRecipientService->getAllMailTypes()
        ]);
    }

    /**
     * 更新收件信箱
     */
    public function update(MailRecipientRequest $request, string $id)
    {
        $validated = $request->validated();
        $result = $this->mailRecipientService->save($validated, $id);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 刪除收件信箱
     */
    public function destroy($id)
    {
        $result = $this->mailRecipientService->delete($id);

        return redirect()
            ->back()
            ->with('result', $result);
    }

    /**
     * 切換啟用狀態
     */
    public function toggleActive(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:mail_recipients,id',
        ]);

        $result = $this->mailRecipientService->toggleStatus($validated['id']);

        return redirect()
            ->back()
            ->with('result', $result);
    }
}
