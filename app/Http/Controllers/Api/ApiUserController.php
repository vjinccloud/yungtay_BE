<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class ApiUserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * 搜尋會員（支援 Select2 AJAX）
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'page' => 'integer|min:1'
        ]);

        try {
            $keyword = $request->get('q');
            $perPage = 15;

            $result = $this->userRepository->searchMembers($keyword, $perPage);

            return response()->json([
                'data' => $result->items(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '搜尋會員失敗'
            ], 500);
        }
    }
}