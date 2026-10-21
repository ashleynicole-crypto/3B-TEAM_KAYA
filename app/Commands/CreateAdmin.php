<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateAdmin extends BaseCommand
{
    protected $group = 'Sun Son Solar';
    protected $name = 'sunson:create-admin';
    protected $description = 'Create the first or an additional admin account interactively.';

    public function run(array $params)
    {
        $db=db_connect();
        if (!$db->tableExists('DEPARTMENT') || !$db->tableExists('EMPLOYEE')) {
            CLI::error('Import database.sql before creating an admin.');
            return EXIT_ERROR;
        }
        $first=trim((string)CLI::prompt('First name'));
        $last=trim((string)CLI::prompt('Last name'));
        $birth=trim((string)CLI::prompt('Birthdate (YYYY-MM-DD)'));
        $gender=trim((string)CLI::prompt('Gender (Female/Male/Other)'));
        $email=trim((string)CLI::prompt('Email'));
        $phone=trim((string)CLI::prompt('Phone number'));
        $address=trim((string)CLI::prompt('Address'));
        $username=trim((string)CLI::prompt('Username'));
        $password=(string)CLI::prompt('Password (12+ characters)');
        if ($first==='' || $last==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<12 || !preg_match('/^[A-Za-z0-9._-]{3,100}$/',$username) || !in_array($gender,['Female','Male','Other'],true) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$birth)) {
            CLI::error('Invalid details. No account was created.');
            return EXIT_ERROR;
        }
        $duplicate=$db->table('EMPLOYEE')->groupStart()->where('username',$username)->orWhere('email',$email)->groupEnd()->countAllResults();
        $duplicate += $db->table('REGISTRATION')->groupStart()->where('username',$username)->orWhere('email',$email)->groupEnd()->countAllResults();
        if ($duplicate) { CLI::error('Username, email, or phone is already in use.'); return EXIT_ERROR; }
        $department=$db->table('DEPARTMENT')->where('department_name','Administration')->get()->getRowArray();
        $db->table('EMPLOYEE')->insert(['first_name'=>$first,'last_name'=>$last,'birthdate'=>$birth,'gender'=>$gender,'email'=>$email,'phone_number'=>$phone,'address'=>$address,'username'=>$username,'password'=>password_hash($password,PASSWORD_DEFAULT),'department_id'=>$department['department_id'],'role'=>'admin']);
        CLI::write('Administrator account created.');
        return EXIT_SUCCESS;
    }
}
