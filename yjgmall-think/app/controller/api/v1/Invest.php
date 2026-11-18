<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\model\InvestLead;
use app\model\InvestProject;
use think\Response;

class Invest extends BaseController
{
    public function index(): Response
    {
        $query = InvestProject::where('status', 'published')->order('id', 'desc');
        if ($q = $this->request->param('q')) {
            $query->whereLike('title', '%' . $q . '%');
        }
        return json(paginate_laravel($query->paginate(['list_rows' => 16])));
    }

    public function show($id): Response
    {
        $item = InvestProject::where('status', 'published')->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        return json_data($item->toArray());
    }

    public function apply(): Response
    {
        $projectId = (int) ($this->request->post('invest_project_id') ?: $this->request->post('project_id') ?: 0);
        $company = trim((string) $this->request->post('company_name', ''));
        $contact = trim((string) ($this->request->post('contact_name') ?: $this->request->post('name') ?: $this->request->post('contact') ?: ''));
        $phone = trim((string) ($this->request->post('contact_phone') ?: $this->request->post('phone') ?: ''));
        $content = trim((string) $this->request->post('content', ''));

        if ($projectId <= 0) {
            return json_error('请选择招商项目', 422);
        }
        if ($contact === '' || $phone === '') {
            return json_error('请填写联系人和电话', 422);
        }
        $project = InvestProject::where('status', 'published')->find($projectId);
        if (!$project) {
            return json_error('招商项目不存在或已下架', 404);
        }

        $lead = InvestLead::create([
            'invest_project_id' => $projectId,
            'user_id' => $this->request->user->id ?? null,
            'company_name' => $company !== '' ? $company : null,
            'contact_name' => $contact,
            'contact_phone' => $phone,
            'content' => $content !== '' ? $content : null,
            'status' => 'new',
        ]);

        return json(['data' => $lead->toArray(), 'message' => '已提交，我们会尽快联系您'], 201);
    }
}
