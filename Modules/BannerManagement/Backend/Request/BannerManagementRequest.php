<?php

namespace Modules\BannerManagement\Backend\Request;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SlimImageRule;
use App\Models\Banner;

class BannerManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $isUpdate = !is_null($id);

        $banner = $isUpdate
            ? Banner::with(['desktopImage', 'mobileImage'])->find($id)
            : null;

        $desktopCleared = $this->boolean('slimDesktopCleared');
        $mobileCleared = $this->boolean('slimMobileCleared');

        $hasDesktop = $isUpdate && !$desktopCleared && optional($banner?->desktopImage)->exists;
        $hasMobile = $isUpdate && !$mobileCleared && optional($banner?->mobileImage)->exists;

        return [
            'title' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],

            'slimDesktop' => array_filter([
                $isUpdate
                    ? ($desktopCleared ? 'required' : 'sometimes')
                    : 'required',
                new SlimImageRule(
                    required: true,
                    maxSizeMB: 8,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    hasOriginalImage: $hasDesktop
                ),
            ]),

            'slimMobile' => array_filter([
                $isUpdate
                    ? ($mobileCleared ? 'required' : 'sometimes')
                    : 'required',
                new SlimImageRule(
                    required: true,
                    maxSizeMB: 5,
                    allowedTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    hasOriginalImage: $hasMobile
                ),
            ]),
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => '標題不得超過 100 個字元',
            'url.max' => '連結網址不得超過 255 個字元',
            'slimDesktop.required' => '桌機版圖片為必填',
            'slimMobile.required' => '手機版圖片為必填',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }

        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }
    }
}
