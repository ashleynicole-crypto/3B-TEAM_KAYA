<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $products = $db->table('PRODUCT')->where('is_active', 1)->orderBy('product_name')->get()->getResultArray();
        $services = $db->table('SERVICE_CATEGORY')->where('is_active', 1)->orderBy('service_name')->get()->getResultArray();
        $testimonials = $db->table('CUSTOMER_TESTIMONIAL t')
            ->select('t.quote, r.first_name, r.last_name')
            ->join('REGISTRATION r', 'r.customer_id = t.customer_id')
            ->where('t.status', 'approved')
            ->where('t.consent_to_publish', 1)
            ->orderBy('t.created_at', 'DESC')
            ->limit(3)
            ->get()
            ->getResultArray();
        $news = $db->table('ANNOUNCEMENT')
            ->whereIn('audience', ['customers', 'all'])
            ->where('publish_at <=', date('Y-m-d H:i:s'))
            ->groupStart()->where('expires_at IS NULL', null, false)->orWhere('expires_at >=', date('Y-m-d H:i:s'))->groupEnd()
            ->orderBy('publish_at', 'DESC')
            ->limit(3)
            ->get()
            ->getResultArray();
        $siteSettings = array_column($db->table('SYSTEM_SETTING')->get()->getResultArray(), 'setting_value', 'setting_key');
        return view('home', compact('products', 'services', 'testimonials', 'news', 'siteSettings'));
    }
}
