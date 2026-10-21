<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $products = db_connect()->table('PRODUCT')->where('is_active', 1)->orderBy('product_name')->get()->getResultArray();
        return view('home', compact('products'));
    }
}
