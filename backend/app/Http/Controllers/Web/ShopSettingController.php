<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\Request;

class ShopSettingController extends Controller
{
    public function edit()
    {
        $settings = ShopSetting::current();

        return view('shop-settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_id' => ['nullable', 'string', 'max:100'],
            'footer_note' => ['nullable', 'string', 'max:255'],
        ]);

        ShopSetting::current()->update($data);

        return redirect()->route('shop-settings.edit')->with('status', 'Shop details saved.');
    }
}
