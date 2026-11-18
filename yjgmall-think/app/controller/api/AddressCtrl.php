<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Address;
use think\Response;

class AddressCtrl extends BaseController
{
    public function index(): Response
    {
        return json_data(Address::where('user_id', $this->request->user->id)->order('id', 'desc')->select()->toArray());
    }

    public function store(): Response
    {
        $data = $this->request->post();
        $data['user_id'] = $this->request->user->id;
        $item = Address::create($data);

        return json_data($item->toArray(), 201);
    }

    public function update($id): Response
    {
        $item = Address::where('user_id', $this->request->user->id)->find($id);
        if (!$item) {
            return json_error('Forbidden', 403);
        }
        $item->save($this->request->post());

        return json_data($item->toArray());
    }

    public function destroy($id): Response
    {
        Address::where('user_id', $this->request->user->id)->where('id', $id)->delete();

        return json_message('ok');
    }
}
