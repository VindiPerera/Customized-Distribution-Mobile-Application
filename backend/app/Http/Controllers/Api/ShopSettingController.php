<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopSetting;

class ShopSettingController extends Controller
{
    public function show()
    {
        return ShopSetting::current();
    }
}
