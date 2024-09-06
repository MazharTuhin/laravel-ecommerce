<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{
    public function BrandList(): JsonResponse
    {
        $data = Brand::all();
        return ResponseHelper::Out('success', $data, 200);
    }
}
