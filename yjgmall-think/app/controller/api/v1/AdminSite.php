<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\model\SiteSetting;
use think\Response;

class AdminSite extends BaseController
{
    public function show(): Response
    {
        return json_data(Site::allSettings());
    }

    public function update(): Response
    {
        $allowed = array_keys(Site::DEFAULTS);
        $payload = $this->request->post();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $val = trim((string) $payload[$key]);
            $row = SiteSetting::where('key', $key)->find();
            if ($row) {
                $row->value = $val;
                $row->save();
            } else {
                SiteSetting::create([
                    'key' => $key,
                    'value' => $val,
                ]);
            }
        }

        return json(['data' => Site::allSettings(), 'message' => '已保存']);
    }
}
