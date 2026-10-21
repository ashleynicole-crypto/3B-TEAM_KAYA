<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Libraries\ActivityLogger;

class Auth extends BaseController
{
    private function employeeDestination(array $employee): string
    {
        if ((int) $employee['must_change_password'] === 1) {
            return 'staff/password';
        }

        return $employee['role'] === 'hr' ? 'staff/users' : 'staff';
    }

    public function loginForm()
    {
        $type = session('user_type');
        $id = (int) session('user_id');

        if ($id > 0 && in_array($type, ['customer', 'employee'], true)) {
            $table = $type === 'customer' ? 'REGISTRATION' : 'EMPLOYEE';
            $key = $type === 'customer' ? 'customer_id' : 'employee_id';
            $account = db_connect()->table($table)
                ->where($key, $id)
                ->where('is_active', 1)
                ->get()
                ->getRowArray();

            if ($account) {
                $mustChangePassword = (int) $account['must_change_password'];
                session()->set([
                    'username' => $account['username'],
                    'must_change_password' => $mustChangePassword,
                ]);

                if ($type === 'customer') {
                    return redirect()->to(site_url($mustChangePassword ? 'dashboard/password' : 'dashboard'));
                }

                session()->set([
                    'role' => $account['role'],
                    'department_id' => $account['department_id'],
                ]);
                return redirect()->to(site_url($this->employeeDestination($account)));
            }

            session()->destroy();
        }

        return view('auth/login');
    }

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
        ActivityLogger::record($this->request, 'customer_registered', 'customer', $model->getInsertID());
        return redirect()->to(site_url('login'))->with('message', 'Your account is ready. Please log in.');
    }

    public function login()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $customer = (new CustomerModel())->where('username', $username)->first();
        if ($customer && (int) $customer['is_active'] === 1 && password_verify($password, $customer['password'])) {
            session()->regenerate();
            session()->set(['user_type' => 'customer', 'user_id' => $customer['customer_id'], 'username' => $customer['username'], 'must_change_password' => (int) $customer['must_change_password']]);
            ActivityLogger::record($this->request, 'customer_login', 'customer', $customer['customer_id']);
            return redirect()->to(site_url($customer['must_change_password'] ? 'dashboard/password' : 'dashboard'));
        }
        $employee = db_connect()->table('EMPLOYEE')->where('username', $username)->where('is_active', 1)->get()->getRowArray();
        if ($employee && password_verify($password, $employee['password'])) {
            session()->regenerate();
            session()->set(['user_type' => 'employee', 'user_id' => $employee['employee_id'], 'username' => $employee['username'], 'role' => $employee['role'], 'department_id' => $employee['department_id'], 'must_change_password' => (int) $employee['must_change_password']]);
            ActivityLogger::record($this->request, 'employee_login', 'employee', $employee['employee_id'], ['role' => $employee['role']]);
            return redirect()->to(site_url($this->employeeDestination($employee)));
        }
        return view('auth/login', ['errors' => ['Invalid username or password.']]);
    }

    public function logout()
    {
        if (in_array(session('user_type'), ['employee', 'customer'], true)) ActivityLogger::record($this->request, 'user_logout', (string) session('user_type'), session('user_id'));
        session()->destroy();
        return redirect()->to(site_url('login'));
    }

    public function createEmployee()
    {
        $creatorId=(int)session('user_id');
        $creator=$creatorId?db_connect()->table('EMPLOYEE')->select('role,is_active,must_change_password')->where('employee_id',$creatorId)->get()->getRowArray():null;
        if (session('user_type') !== 'employee' || !$creator || !(int)$creator['is_active']) {
            session()->destroy();
            return redirect()->to(site_url('login'));
        }
        session()->set(['role'=>$creator['role'],'must_change_password'=>(int)$creator['must_change_password']]);
        if ($creator['must_change_password']) return redirect()->to(site_url('staff/password'));
        if (!in_array($creator['role'], ['admin', 'hr'], true)) return redirect()->to(site_url('staff'));
        $rules = ['first_name'=>'required|max_length[50]','middle_name'=>'permit_empty|max_length[50]','last_name'=>'required|max_length[50]','birthdate'=>'required|valid_date[Y-m-d]','gender'=>'required|in_list[Female,Male,Other]','email'=>'required|valid_email|max_length[255]','phone_number'=>'required|max_length[20]','address'=>'required|max_length[255]','username'=>'required|regex_match[/^[A-Za-z0-9._-]{3,100}$/]','password'=>'required|min_length[12]','department_id'=>'required|is_natural_no_zero','role'=>'required|in_list[admin,hr,dispatcher,technician,employee]'];
        if (! $this->validate($rules)) return redirect()->to(site_url('staff/users'))->with('form_errors', $this->validator->getErrors());
        $data = $this->request->getPost(array_keys($rules));
        if (session('role')==='hr' && in_array($data['role'],['admin','hr'],true)) return redirect()->to(site_url('staff/users'))->with('form_errors',['HR accounts cannot provision administrator or HR roles.']);
        $db=db_connect();
        $conflict=$db->table('EMPLOYEE')->groupStart()->where('username',$data['username'])->orWhere('email',$data['email'])->groupEnd()->countAllResults();
        $conflict += $db->table('REGISTRATION')->groupStart()->where('username',$data['username'])->orWhere('email',$data['email'])->groupEnd()->countAllResults();
        if ($conflict) return redirect()->to(site_url('staff/users'))->with('form_errors',['Username or email is already in use.']);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['must_change_password'] = 1;
        $db->table('EMPLOYEE')->insert($data);
        ActivityLogger::record($this->request, 'employee_created', 'employee', $db->insertID(), ['role' => $data['role'], 'department_id' => $data['department_id']]);
        return redirect()->to(site_url('staff/users'))->with('message', 'Employee account created.');
    }
}
