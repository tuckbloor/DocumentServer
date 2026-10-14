<?php

namespace App\Http\Controllers\DocumentServer;

use App\Http\Controllers\Controller;
use App\Support\DocumentServer\DocumentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentFavouriteController extends Controller
{
    public function toggle(Request $request, int $document): RedirectResponse
    {
        $doc = DocumentAccess::document($document, (int) $request->user()->id);
        $key = ['document_id' => $doc->id, 'user_id' => $request->user()->id];
        if (DB::table('document_favourites')->where($key)->exists()) {
            DB::table('document_favourites')->where($key)->delete();
        } else {
            DB::table('document_favourites')->insert($key + ['created_at' => now(), 'updated_at' => now()]);
        }
        return back();
    }
}
