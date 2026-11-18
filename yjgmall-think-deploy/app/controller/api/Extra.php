<?php

namespace app\controller\api;

use app\BaseController;
use think\Response;

/**
 * Extra modules stubs so frontend routes don't 404.
 */
class Extra extends BaseController
{
    private function emptyPage(): Response
    {
        return json([
            'data' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 16,
                'total' => 0,
            ],
        ]);
    }

    public function jobs(): Response { return $this->emptyPage(); }
    public function jobShow($id): Response { return json_data(null); }
    public function knows(): Response { return $this->emptyPage(); }
    public function knowShow($id): Response { return json_data(null); }
    public function answerKnow($id): Response { return json(['data' => ['id' => 0]], 201); }
    public function clubs(): Response { return $this->emptyPage(); }
    public function clubShow($id): Response { return json_data(null); }
    public function specials(): Response { return $this->emptyPage(); }
    public function specialShow($id): Response { return json_data(null); }
    public function photos(): Response { return $this->emptyPage(); }
    public function photoShow($id): Response { return json_data(null); }
    public function videos(): Response { return $this->emptyPage(); }
    public function videoShow($id): Response { return json_data(null); }
    public function groups(): Response { return $this->emptyPage(); }
    public function groupShow($id): Response { return json_data(null); }
    public function exhibits(): Response { return $this->emptyPage(); }
    public function exhibitShow($id): Response { return json_data(null); }
    public function gifts(): Response { return $this->emptyPage(); }
    public function giftShow($id): Response { return json_data(null); }
    public function redeemGift($id): Response { return json_message('ok'); }
    public function friends(): Response { return json_data([]); }
    public function addFriend(): Response { return json_message('ok', 201); }
    public function memberJobs(): Response { return $this->emptyPage(); }
    public function storeJob(): Response { return json(['data' => []], 201); }
    public function memberJobShow($id): Response { return json_data(null); }
    public function updateJob($id): Response { return json_message('ok'); }
    public function destroyJob($id): Response { return json_message('ok'); }
    public function memberKnows(): Response { return $this->emptyPage(); }
    public function storeKnow(): Response { return json(['data' => []], 201); }
    public function memberKnowShow($id): Response { return json_data(null); }
    public function updateKnow($id): Response { return json_message('ok'); }
    public function destroyKnow($id): Response { return json_message('ok'); }
    public function memberClubs(): Response { return $this->emptyPage(); }
    public function storeClub(): Response { return json(['data' => []], 201); }
    public function memberClubShow($id): Response { return json_data(null); }
    public function updateClub($id): Response { return json_message('ok'); }
    public function destroyClub($id): Response { return json_message('ok'); }

    public function brands(): Response { return $this->emptyPage(); }
    public function brandShow($id): Response { return json_data(null); }
    public function downloads(): Response { return $this->emptyPage(); }
    public function downloadShow($id): Response { return json_data(null); }
    public function downloadHit($id): Response { return json_message('ok'); }
    public function invest(): Response { return $this->emptyPage(); }
    public function investShow($id): Response { return json_data(null); }
    public function investApply(): Response { return json_message('ok', 201); }
    public function pageShow($slug): Response { return json_data(['slug' => $slug, 'title' => '', 'content' => '']); }
    public function vipGrades(): Response { return json_data([]); }
    public function vipApply(): Response { return json_message('ok', 201); }
    public function adPlaces(): Response { return json_data([]); }
    public function quotes(): Response { return json_data([]); }
    public function inquiryStore(): Response { return json(['data' => ['id' => 0], 'message' => 'ok'], 201); }
}
