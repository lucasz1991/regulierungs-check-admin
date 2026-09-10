<?php

namespace App\Services\Promotion;

use App\Enums\PromotionDigitalDeliveryStatus;
use App\Enums\PromotionFulfillmentMode;
use App\Enums\PromotionGiftCodeStatus;
use App\Enums\PromotionNotificationStatus;
use App\Mail\PromotionDigitalDeliveryMail;
use App\Models\PromotionGiftCode;
use App\Models\PromotionNotificationDelivery;
use App\Models\PromotionPrize;
use App\Models\PromotionSpinResult;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class PromotionDigitalDeliveryService
{
    public function __construct(private readonly PromotionAuditChain $audit, private readonly PromotionSettingsService $settings) {}

    /** @return array{created:int,duplicates:int} */
    public function addCodes(int $prizeId, string $source, User $admin): array
    {
        $this->assertAdmin($admin); $codes = collect(preg_split('/\R/u', $source) ?: [])->map(fn ($v) => trim((string) $v))->filter()->values();
        if ($codes->isEmpty() || $codes->count() !== $codes->unique()->count()) throw new DomainException('Bitte eindeutige Gutscheincodes, je Zeile einen, eingeben.');
        $created = DB::transaction(function () use ($prizeId, $codes, $admin) {
            $prize = PromotionPrize::query()->lockForUpdate()->findOrFail($prizeId); if ($prize->fulfillment_mode !== PromotionFulfillmentMode::ExternalAdmin) throw new DomainException('Nur externe digitale Gewinne können Codes erhalten.');
            $count = 0; foreach ($codes as $code) { $fp = hash_hmac('sha256', $code, (string) config('app.key')); if (PromotionGiftCode::query()->where('code_fingerprint', $fp)->exists()) continue; PromotionGiftCode::create(['campaign_id'=>$prize->campaign_id,'prize_id'=>$prize->id,'code_encrypted'=>Crypt::encryptString($code),'code_fingerprint'=>$fp,'status'=>PromotionGiftCodeStatus::Available,'uploaded_by'=>$admin->id]); $count++; } return $count;
        }, 5);
        $this->deliverWaiting($prizeId); return ['created'=>$created,'duplicates'=>$codes->count()-$created];
    }

    public function approve(PromotionSpinResult $result, User $admin): PromotionSpinResult
    {
        $this->assertAdmin($admin); $result = DB::transaction(function () use ($result, $admin) {
            $result = PromotionSpinResult::query()->with('ticket')->lockForUpdate()->findOrFail($result->id); if ($result->is_test || ! $result->is_final || $result->superseded_at || $result->fulfilled_at || $result->fulfillment_mode_snapshot !== PromotionFulfillmentMode::ExternalAdmin) throw new DomainException('Dieser Gewinn kann nicht digital freigegeben werden.');
            if (! $result->digital_delivery_approved_at) $result->forceFill(['digital_delivery_approved_by'=>$admin->id,'digital_delivery_approved_at'=>now(),'digital_delivery_status'=>PromotionDigitalDeliveryStatus::AwaitingCode])->save(); return $result->fresh(['ticket.user','campaign','prize']);
        }, 5);
        $this->notice($result, 'approved'); return $this->attemptDelivery($result);
    }

    public function attemptDelivery(PromotionSpinResult $result): PromotionSpinResult
    {
        $result = DB::transaction(function () use ($result) {
            $result = PromotionSpinResult::query()->with(['ticket.user.customer','giftCode'])->lockForUpdate()->findOrFail($result->id); if (! $result->digital_delivery_approved_at || $result->fulfilled_at || $result->is_test) return $result;
            $customer = $result->ticket?->user?->customer; if (! $customer || collect([$customer->first_name,$customer->last_name,$customer->street,$customer->postal_code,$customer->city,$customer->country])->contains(fn ($v) => trim((string)$v)==='')) { $result->forceFill(['digital_delivery_status'=>PromotionDigitalDeliveryStatus::AwaitingProfile])->save(); return $result; }
            $code = $result->giftCode ?: PromotionGiftCode::query()->where('prize_id',$result->prize_id)->where('status',PromotionGiftCodeStatus::Available)->orderBy('id')->lockForUpdate()->first(); if (! $code) { $result->forceFill(['digital_delivery_status'=>PromotionDigitalDeliveryStatus::AwaitingCode])->save(); return $result; }
            if (! $code->spin_result_id) $code->forceFill(['spin_result_id'=>$result->id,'status'=>PromotionGiftCodeStatus::Reserved,'reserved_at'=>now()])->save(); $result->forceFill(['digital_delivery_status'=>PromotionDigitalDeliveryStatus::Reserved])->save(); return $result->fresh(['ticket.user','campaign','giftCode']);
        }, 5);
        if ($result->digital_delivery_status === PromotionDigitalDeliveryStatus::AwaitingProfile) { $this->notice($result,'profile_required'); return $result; }
        if ($result->digital_delivery_status !== PromotionDigitalDeliveryStatus::Reserved) return $result;
        try { Mail::to($result->ticket->user->email)->send(new PromotionDigitalDeliveryMail($result,'code',rtrim($this->settings->redemptionBaseUrl(),'/').'/gluecksrad',Crypt::decryptString($result->giftCode->getRawOriginal('code_encrypted')))); return $this->complete($result, true); } catch (Throwable $e) { report($e); return $this->complete($result, false, $e); }
    }

    public function retry(PromotionSpinResult $result, User $admin): PromotionSpinResult { $this->assertAdmin($admin); return $this->attemptDelivery($result); }
    public function deliverEligibleForUser(User $user): void { PromotionSpinResult::query()->whereHas('ticket',fn($q)=>$q->where('user_id',$user->id))->whereNotNull('digital_delivery_approved_at')->whereNull('fulfilled_at')->where('is_test',false)->each(fn($r)=>$this->attemptDelivery($r)); }
    private function deliverWaiting(int $prizeId): void { PromotionSpinResult::query()->where('prize_id',$prizeId)->whereNotNull('digital_delivery_approved_at')->whereNull('fulfilled_at')->where('is_test',false)->each(fn($r)=>$this->attemptDelivery($r)); }
    private function notice(PromotionSpinResult $result,string $type): void { $delivery=PromotionNotificationDelivery::firstOrCreate(['spin_result_id'=>$result->id,'type'=>$type],['status'=>PromotionNotificationStatus::Open]); try { $result->loadMissing('ticket.user'); Mail::to($result->ticket->user->email)->send(new PromotionDigitalDeliveryMail($result,$type,rtrim($this->settings->redemptionBaseUrl(),'/').'/gluecksrad')); $delivery->forceFill(['status'=>PromotionNotificationStatus::Sent,'sent_at'=>now(),'last_attempted_at'=>now()])->save(); } catch(Throwable $e) { report($e); $delivery->forceFill(['status'=>PromotionNotificationStatus::Failed,'failed_at'=>now(),'last_attempted_at'=>now(),'error_digest'=>hash('sha256',$e::class.'|'.$e->getMessage())])->save(); } }
    private function complete(PromotionSpinResult $result,bool $sent,?Throwable $error=null): PromotionSpinResult { return DB::transaction(function()use($result,$sent,$error){ $result=PromotionSpinResult::query()->with(['giftCode','ticket.user'])->lockForUpdate()->findOrFail($result->id); $code=PromotionGiftCode::query()->lockForUpdate()->findOrFail($result->giftCode->id); $notice=PromotionNotificationDelivery::firstOrCreate(['spin_result_id'=>$result->id,'type'=>'code'],['status'=>PromotionNotificationStatus::Open]); if($sent){$code->forceFill(['status'=>PromotionGiftCodeStatus::Sent,'sent_at'=>now()])->save();$result->forceFill(['digital_delivery_status'=>PromotionDigitalDeliveryStatus::Sent,'fulfilled_at'=>now(),'fulfilled_by'=>$result->digital_delivery_approved_by])->save();$notice->forceFill(['status'=>PromotionNotificationStatus::Sent,'sent_at'=>now(),'last_attempted_at'=>now()])->save();$result->ticket->user->receiveMessage('Dein Gewinn wurde versendet','Dein digitaler Gewinn wurde per E-Mail versendet. Der Gutscheincode ist nur in dieser E-Mail enthalten.',$result->digital_delivery_approved_by);}else{$code->forceFill(['status'=>PromotionGiftCodeStatus::Failed])->save();$result->forceFill(['digital_delivery_status'=>PromotionDigitalDeliveryStatus::Failed])->save();$notice->forceFill(['status'=>PromotionNotificationStatus::Failed,'failed_at'=>now(),'last_attempted_at'=>now(),'error_digest'=>hash('sha256',$error::class.'|'.$error->getMessage())])->save();}return $result;},5); }
    private function assertAdmin(User $user): void { if ($user->role!=='admin'||!(bool)$user->status) throw new DomainException('Nur ein aktiver Volladmin darf digitale Gewinne verwalten.'); }
}
