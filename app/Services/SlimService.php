<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SlimService {
    const FAILURE = 'failure';
    const SUCCESS = 'success';

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public static function getImages($value) {

        if ($value === false) {
            return false;
        }

        $data = SlimService::parseInput($value);
        // if ($inputValue) {
        //     array_push($data, $inputValue);
        // }

        return $data;
    }

    private static function parseInput($value) {

        $value = stripslashes($value);

        // The data is posted as a JSON String so to be used it needs to be deserialized first
        $data = json_decode($value);

        // shortcut
        $input = null;
        $actions = null;
        $output = null;
        $meta = null;

        if (isset ($data->input)) {

            $inputData = null;
            if (isset($data->input->image)) {
                $inputData = SlimService::getBase64Data($data->input->image);
            }


            $input = array(
                'data' => $inputData,
                'name' => $data->input->name,
                'type' => $data->input->type,
                'size' => $data->input->size,
                'width' => $data->input->width,
                'height' => $data->input->height,
            );

        }

        if (isset($data->output)) {

            $outputDate = null;
            if (isset($data->output->image)) {
                $outputData = SlimService::getBase64Data($data->output->image);
            }

            $output = array(
                'data' => $outputData,
                'name' => $data->output->name,
                'type' => $data->output->type,
                'width' => $data->output->width,
                'height' => $data->output->height
            );
        }

        if (isset($data->actions)) {
            $actions = array(
                'crop' => $data->actions->crop ? array(
                    'x' => $data->actions->crop->x,
                    'y' => $data->actions->crop->y,
                    'width' => $data->actions->crop->width,
                    'height' => $data->actions->crop->height,
                    'type' => $data->actions->crop->type
                ) : null,
                'size' => $data->actions->size ? array(
                    'width' => $data->actions->size->width,
                    'height' => $data->actions->size->height
                ) : null,
                'rotation' => $data->actions->rotation,
                'filters' => $data->actions->filters ? array(
                    'sharpen' => $data->actions->filters->sharpen
                ) : null
            );
        }

        if (isset($data->meta)) {
            $meta = $data->meta;
        }

        // We've sanitized the base64data and will now return the clean file object
        return array(
            'input' => $input,
            'output' => $output,
            'actions' => $actions,
            'meta' => $meta
        );
    }

    private static function isImage($file) {
        return in_array($file->getMimeType(), ['image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/svg+xml']);
    }

    public static function saveFile($data, $name, $path = 'uploads/banner/', $uid = true) {
        $fileName = $name;
        if ($uid) {
            $name = uniqid() . '_' . $name;
        }

        // 创建完整路径
        $fullPath = $path . $name;

        // 使用 put 方法保存文件数据
        Storage::disk('uploads')->put($fullPath, $data);

        if (!Storage::disk('uploads')->exists($fullPath)) {
            return [
                'success' => false,
                'error' => 'File not saved',
                'name' => $fileName,
                'path' => null,
            ];
        }

        $fullPath= 'uploads/' . $fullPath;

        return [
            'success' => true,
            'name' => $fileName,
            'path' => $fullPath,
        ];
    }

    public function outputJSON($data) {
        return response()->json($data);
    }

    public function sanitizeFileName($str) {
        $str = preg_replace('([^\w\s\d\-_~,;\[\]\(\).])', '', $str);
        $str = preg_replace('([\.]{2,})', '', $str);
        return $str;
    }


    private function save($data, $path) {
        if (!Storage::put($path, $data)) {
            return false;
        }
        return true;
    }

    private static function getBase64Data($data) {
        return base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));
    }
}
