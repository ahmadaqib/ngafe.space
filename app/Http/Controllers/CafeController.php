<?php
namespace App\Http\Controllers;
use App\Domain\Cafe\Models\Cafe; use App\Domain\Cafe\Models\CafeStatus; use App\Domain\Cafe\Support\OpeningHours; use Carbon\CarbonImmutable;
class CafeController extends Controller { public function show(string $city,string $slug){ $user=request()->user(); $cafe=Cafe::query()->where(['city'=>$city,'slug'=>$slug,'status'=>CafeStatus::Active])->with(['categories','photos','reviews'=>fn($q)=>$q->where('status','published')->when($user,fn($q)=>$q->orWhere('user_id',$user->id))->with('photos')])->firstOrFail(); return response()->view('cafe.show',['cafe'=>$cafe,'opening'=>OpeningHours::statusNow($cafe,CarbonImmutable::now())])->header('Cache-Control','public, max-age=300'); } }
