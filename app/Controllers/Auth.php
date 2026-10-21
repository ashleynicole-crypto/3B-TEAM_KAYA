<?php

namespace App\Controllers;

use App\Models\CustomerModel;

class Auth extends BaseController
{
    public function loginForm(): string { return view('auth/login'); }
    public function registerForm(): string { return view('auth/register'); }

    public function register()
    {
        $rules = [
            'first_name' => 'required|max_length[50]', 'last_name' => 'required|max_length[50]',
            'middle_name' => 'permit_empty|max_length[50]', 'birthdate' => 'required|valid_date[Y-m-d]',
            'gender' => 'required|in_list[Female,Male,Other]', 'email' => 'required|valid_email|max_length[255]',
            'phone_number' => 'required|max_length[20]', 'address' => 'required|max_length[255]',
            'username' => 'required|regex_match[/^[A-Za-z0-9._-]{3,100}$/]',
            'password' => 'required|min_length[8]|regex_match[/[A-Za-z]/]|regex_match[/[0-9]/]',
        ];
        if (! $this->validate($rules)) {
            return view('auth/register', ['errors' => $this->validator->getErrors()]);
        }
        $model = new CustomerModel();
        $data = $this->request->getPost(array_keys($rules));
        $employeeConflict = db_connect()->table('EMPLOYEE')->groupStart()->where('username',$data['username'])->orWhere('email',$data['email'])->groupEnd()->countAllResults();
        $customerConflict=db_connect()->table('REGISTRATION')->groupStart()->where('username',$data['username'])->orWhere('email',$data['email'])->groupEnd()->countAllResults();
        if ($employeeConflict || $customerConflict) {
            return view('auth/register', ['errors' => ['That username or email is already registered.']]);
        }
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $model->insert($data);
        return redirect()->to(site_url('login'))->with('message', 'Your account is ready. Please log in.');
    }

    public function login()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $customer = (new CustomerModel())->where('username', $username)->first();
        if ($customer && password_verify($password, $customer['password'])) {
            session()->regenerate();
            session()->set(['user_type' => 'customer', 'user_id' => $customer['customer_id'], 'username' => $customer['username']]);
            return redirect()->to(site_url('dashboard'));
        }
        $employee = db_connect()->table('EMPLOYEE')->where('username', $username)->where('is_active', 1)->get()->getRowArray();
        if ($employee && password_verify($password, $employee['password'])) {
            session()->regenerate();
            session()->set(['user_type' => 'employee', 'user_id' => $employee['employee_id'], 'username' => $employee['username'], 'role' => $employee['role'], 'department_id' => $employee['department_id']]);
            return redirect()->to(site_url('staff'));
        }
        return view('auth/login', ['errors' => ['Invalid username or password.']]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'));
    }

    public function createEmployee()
    {
        if (session('user_type') !== 'employee' || ! in_array(session('role'), ['admin', 'hr'], true)) {
            return redirect()->to(site_url('login'));
        }
        $rules = ['first_name'=>'required|max_length[50]','middle_name'=>'permit_empty|max_length[50]','last_name'=>'required|max_length[50]','birthdate'=>'required|valid_date[Y-m-d]','gender'=>'required|in_list[Female,Male,Other]','email'=>'required|valid_email|max_length[255]','phone_number'=>'required|max_length[20]','address'=>'required|max_length[255]','username'=>'required|regex_match[/^[A-Za-z0-9._-]{3,100}$/]','password'=>'required|min_length[12]','department_id'=>'required|is_natural_no_zero','role'=>'required|in_list[admin,hr,dispatcher,technician,employee]'];
        if (! $this->validate($rules)) return redirect()->to(site_url('staff/users'))->with('form_errors', $this->validator->getErrors());
        $data = $this->request->getPost(array_keys($rules));
        if (session('role')==='hr' && in_array($data['role'],['admin','hr'],true)) return redirect()->to(site_url('staff/users'))->with('form_errors',['HR accounts cannot provision administrator or HR roles.']);
        $db=db_connect();
        $conflict=$db->table('EMPLOYEE')->groupStart()->where('username',$data['username'])->orWhere('email',$data['email'])->groupEnd()->countAllResults();
        $conflict += $db->table('REGISTRATION')->groupStart()->where('username',$data['username'])->orWhere('email',$data['email'])->groupEnd()->countAllResults();
        if ($conflict) return redirect()->to(site_url('staff/users'))->with('form_errors',['Username or email is already in use.']);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $db->table('EMPLOYEE')->insert($data);
        return redirect()->to(site_url('staff/users'))->with('message', 'Employee account created.');
    }
}
