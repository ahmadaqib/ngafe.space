<?php

namespace App\Http\Controllers;

use App\Domain\Moderation\Actions\SubmitReport;
use App\Domain\Moderation\Models\ReportReason;
use App\Domain\Review\Models\Photo;
use App\Domain\Review\Models\Review;
use App\Http\Requests\StoreReportRequest;
use Illuminate\Http\RedirectResponse;

final class ReportController extends Controller
{
    public function review(StoreReportRequest $request, Review $review, SubmitReport $submitReport): RedirectResponse
    {
        $data = $request->validated();
        $submitReport->handle($request->user(), $review, ReportReason::from($data['reason']), $data['note'] ?? null);

        return back()->with('toast_success', 'Laporan diterima. Makasih sudah ikut menjaga ruang ini.');
    }

    public function photo(StoreReportRequest $request, Photo $photo, SubmitReport $submitReport): RedirectResponse
    {
        $data = $request->validated();
        $submitReport->handle($request->user(), $photo, ReportReason::from($data['reason']), $data['note'] ?? null);

        return back()->with('toast_success', 'Laporan foto diterima.');
    }
}
