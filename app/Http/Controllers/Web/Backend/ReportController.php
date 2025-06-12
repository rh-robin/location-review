<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // show all report
        // table columns: Location, Review, Reason, Report, action:(view, delete)
        // details page: location, review, reason, report, images, reported by:(name, email)
    }

    public function replyToReport(Request $request)
    {

    }

    public function destroy($id)
    {
        // delete report
    }
}
