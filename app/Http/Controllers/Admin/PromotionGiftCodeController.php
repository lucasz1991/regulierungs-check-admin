<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Promotion\PromotionDigitalDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PromotionGiftCodeController extends Controller
{
    public function store(Request $request, PromotionDigitalDeliveryService $delivery): RedirectResponse
    {
        $actor = $request->user(); abort_unless($actor instanceof User && $actor->isAdmin() && $actor->isActive(), 403);
        $data = $request->validate(['prize_id' => ['required','integer','exists:prizes,id'], 'codes' => ['required','string','max:100000']]);
        $result = $delivery->addCodes((int) $data['prize_id'], $data['codes'], $actor);
        return back()->with('status', $result['created'].' Amazon-Code(s) sicher hinterlegt. Wartende Freigaben wurden direkt geprüft.');
    }
}
