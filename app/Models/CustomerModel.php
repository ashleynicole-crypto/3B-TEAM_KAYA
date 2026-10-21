<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'REGISTRATION';
    protected $primaryKey = 'customer_id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'first_name', 'middle_name', 'last_name', 'birthdate', 'gender',
        'email', 'phone_number', 'address', 'username', 'password', 'promotional_opt_in',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
