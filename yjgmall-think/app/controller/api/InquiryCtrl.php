<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Inquiry;
use think\Response;

class InquiryCtrl extends BaseController
{
    public function store(): Response
    {
        $data = $this->request->post();
        $data['user_id'] = $this->request->user->id ?? null;
        $data['status'] = $data['status'] ?? 'pending';
        $item = Inquiry::create($data);

        return json_data($item->toArray(), 201);
    }

    public function index(): Response
    {
        return json_data(Inquiry::where('user_id', $this->request->user->id)->order('id', 'desc')->select()->toArray());
    }

    public function reply($id): Response
    {
        $item = Inquiry::where('user_id', $this->request->user->id)->find($id);
        if (!$item) {
            return json_error('Forbidden', 403);
        }
        $item->reply = $this->request->post('reply', '');
        $item->save();

        return json_data($item->toArray());
    }

    public function updateStatus($id): Response
    {
        $item = Inquiry::where('user_id', $this->request->user->id)->find($id);
        if (!$item) {
            return json_error('Forbidden', 403);
        }
        $item->status = $this->request->post('status', $item->status);
        $item->save();

        return json_data($item->toArray());
    }
}
