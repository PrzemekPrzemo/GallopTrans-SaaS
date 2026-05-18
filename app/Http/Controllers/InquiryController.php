<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index()
    {
        return view('inquiries.index', [
            'inquiries' => Inquiry::orderByDesc('id')->paginate(25),
        ]);
    }

    public function updateStatus(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,in_progress,quoted,closed,spam'],
        ]);
        $inquiry->update($data);
        return back()->with('success', 'Status zaktualizowany.');
    }

    public function destroy(Inquiry $inquiry)
    {
        $inquiry->delete();
        return back()->with('success', 'Zapytanie usunięte.');
    }
}
