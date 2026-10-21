<?php

namespace App\Controllers;

use App\Libraries\ActivityLogger;

class Portal extends BaseController
{
    private function signedIn(string $type = 'employee'): bool
    {
        if (session('user_type') !== $type || (int) session('user_id') <= 0) return false;
        $table=$type==='customer'?'REGISTRATION':'EMPLOYEE'; $key=$type==='customer'?'customer_id':'employee_id';
        $account=db_connect()->table($table)->select('is_active,must_change_password,username'.($type==='employee'?',role,department_id':''))->where($key,(int)session('user_id'))->get()->getRowArray();
        if (!$account || !(int)$account['is_active']) { session()->destroy(); return false; }
        session()->set('must_change_password',(int)$account['must_change_password']);
        session()->set('username',$account['username']);
        if ($type==='employee') session()->set(['role'=>$account['role'],'department_id'=>$account['department_id']]);
        return true;
    }

    private function staffRoles(array $roles): bool
    {
        return $this->signedIn() && !session('must_change_password') && in_array(session('role'), $roles, true);
    }

    private function audit(string $action, string $entity, $entityId = null, array $details = []): void
    {
        ActivityLogger::record($this->request, $action, $entity, $entityId, $details);
    }

    private function isServiceAvailableAt(int $serviceId, int $timestamp): bool
    {
        $db=db_connect();
        $service=$db->table('SERVICE_CATEGORY')->where('service_id',$serviceId)->where('is_active',1)->get()->getRowArray();
        if (!$service) return false;
        $slots=$db->table('SERVICE_AVAILABILITY')->where('service_id',$serviceId)->where('is_available',1)->get()->getResultArray();
        if (!$slots) return true;
        $weekday=(int)date('w',$timestamp); $clock=date('H:i:s',$timestamp);
        $endTimestamp=$timestamp+max(1,(int)($service['duration_minutes']??60))*60;
        if (date('Y-m-d',$endTimestamp)!==date('Y-m-d',$timestamp)) return false;
        $endClock=date('H:i:s',$endTimestamp);
        foreach($slots as $slot) if ((int)$slot['weekday']===$weekday && $clock >= $slot['starts_at'] && $endClock <= $slot['ends_at']) return true;
        return false;
    }

    public function customer()
    {
        if (! $this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        $db = db_connect(); $id = (int) session('user_id');
        $profile = $db->table('REGISTRATION')->where('customer_id', $id)->get()->getRowArray();
        $bookings = $db->table('SERVICE_BOOKING b')->select('b.*, s.service_name, e.first_name AS tech_first, e.last_name AS tech_last')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->join('EMPLOYEE e','e.employee_id=b.assigned_technician_id','left')->where('b.customer_id',$id)->orderBy('b.scheduled_at','DESC')->get()->getResultArray();
        $services = $db->table('SERVICE_CATEGORY')->where('is_active',1)->orderBy('service_name')->get()->getResultArray();
        $products = $db->table('PRODUCT')->where('is_active',1)->orderBy('product_name')->get()->getResultArray();
        $notificationQuery = $db->table('CUSTOMER_NOTIFICATION')->where('customer_id',$id);
        $announcementQuery = $db->table('ANNOUNCEMENT')->groupStart()->where('audience','customers')->orWhere('audience','all')->groupEnd()->where('publish_at <=',date('Y-m-d H:i:s'))->groupStart()->where('expires_at IS NULL',null,false)->orWhere('expires_at >=',date('Y-m-d H:i:s'))->groupEnd();
        if (! $profile['promotional_opt_in']) {
            $notificationQuery->where('notification_type !=', 'promotion');
            $announcementQuery->where('announcement_type !=', 'promotion');
        }
        $notifications = $notificationQuery->orderBy('created_at','DESC')->limit(10)->get()->getResultArray();
        $announcements = $announcementQuery->orderBy('publish_at','DESC')->limit(6)->get()->getResultArray();
        $tickets = $db->table('SUPPORT_TICKET')->where('customer_id',$id)->orderBy('created_at','DESC')->get()->getResultArray();
        $history = $db->table('SERVICE_BOOKING b')->select('b.booking_id,b.scheduled_at,b.status,s.service_name,p.amount,p.status AS payment_status,p.reference_number')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->join('PAYMENT p','p.booking_id=b.booking_id','left')->where('b.customer_id',$id)->whereIn('b.status',['completed','cancelled'])->orderBy('b.scheduled_at','DESC')->get()->getResultArray();
        $testimonials = $db->table('CUSTOMER_TESTIMONIAL')->where('customer_id',$id)->orderBy('created_at','DESC')->get()->getResultArray();
        return view('portal/customer', compact('profile','bookings','services','products','notifications','announcements','tickets','history','testimonials'));
    }

    public function customerPasswordForm()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        return view('portal/password', ['accountType' => 'customer']);
    }

    public function changeCustomerPassword()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        $id = (int) session('user_id'); $db = db_connect();
        $account = $db->table('REGISTRATION')->where('customer_id', $id)->where('is_active', 1)->get()->getRowArray();
        $current = (string) $this->request->getPost('current_password');
        $new = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('confirm_password');
        if (!$account || !password_verify($current, $account['password'])) return redirect()->to(site_url('dashboard/password'))->with('error', 'Enter your current password correctly.');
        if (strlen($new) < 12 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new) || $new !== $confirm) return redirect()->to(site_url('dashboard/password'))->with('error', 'Use a matching password of at least 12 characters with a letter and a number.');
        $db->table('REGISTRATION')->where('customer_id', $id)->update(['password' => password_hash($new, PASSWORD_DEFAULT), 'must_change_password' => 0]);
        $this->audit('customer_password_changed','customer',$id);
        session()->set('must_change_password', 0);
        return redirect()->to(site_url('dashboard'))->with('message', 'Password updated.');
    }

    public function book()
    {
        if (! $this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        $rules=['service_id'=>'required|is_natural_no_zero','scheduled_at'=>'required','customer_notes'=>'permit_empty|max_length[2000]'];
        if (! $this->validate($rules)) return redirect()->to(site_url('dashboard'))->with('error','Choose a service and appointment time.');
        $date = strtotime((string)$this->request->getPost('scheduled_at'));
        if (!$date || $date < time()) return redirect()->to(site_url('dashboard'))->with('error','Choose a future appointment time.');
        $db=db_connect(); $service=(int)$this->request->getPost('service_id');
        if (!$this->isServiceAvailableAt($service,$date)) return redirect()->to(site_url('dashboard'))->with('error','That service is unavailable at the selected time.');
        $db->table('SERVICE_BOOKING')->insert(['customer_id'=>(int)session('user_id'),'service_id'=>$service,'scheduled_at'=>date('Y-m-d H:i:s',$date),'customer_notes'=>trim((string)$this->request->getPost('customer_notes'))]);
        $this->audit('booking_requested', 'booking', $db->insertID(), ['service_id' => $service]);
        return redirect()->to(site_url('dashboard'))->with('message','Your service request was submitted.');
    }

    public function requestReschedule(int $id)
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        $time = strtotime((string) $this->request->getPost('requested_at'));
        if (!$time || $time < time()) return redirect()->to(site_url('dashboard'))->with('error', 'Choose a future time for your reschedule request.');
        $db = db_connect();
        $booking = $db->table('SERVICE_BOOKING')->where('booking_id', $id)->where('customer_id', (int) session('user_id'))->whereIn('status', ['approved','scheduled'])->get()->getRowArray();
        if (!$booking) return redirect()->to(site_url('dashboard'))->with('error', 'Only confirmed appointments can be rescheduled.');
        if (!$this->isServiceAvailableAt((int)$booking['service_id'],$time)) return redirect()->to(site_url('dashboard'))->with('error','That time is outside the service availability. Choose another time.');
        $db->table('SERVICE_BOOKING')->where('booking_id', $id)->update(['reschedule_requested_at' => date('Y-m-d H:i:s', $time)]);
        $this->audit('reschedule_requested', 'booking', $id, ['requested_at' => date('Y-m-d H:i:s', $time)]);
        return redirect()->to(site_url('dashboard'))->with('message', 'Your reschedule request was sent for review. The current appointment remains in place until the team confirms it.');
    }

    public function cancel(int $id)
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        db_connect()->table('SERVICE_BOOKING')->where('booking_id',$id)->where('customer_id',(int)session('user_id'))->whereIn('status',['requested','approved','scheduled'])->update(['status'=>'cancelled','reschedule_requested_at'=>null]);
        $this->audit('booking_cancelled', 'booking', $id);
        return redirect()->to(site_url('dashboard'))->with('message','Booking updated.');
    }

    public function profile()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        $id=(int)session('user_id'); $db=db_connect();
        $current=$db->table('REGISTRATION')->where('customer_id',$id)->get()->getRowArray();
        $rules=['first_name'=>'required|max_length[50]','middle_name'=>'permit_empty|max_length[50]','last_name'=>'required|max_length[50]','birthdate'=>'required|valid_date[Y-m-d]','gender'=>'required|in_list[Female,Male,Other]','email'=>'required|valid_email|max_length[255]','phone_number'=>'required|max_length[20]','address'=>'required|max_length[255]','username'=>'required|regex_match[/^[A-Za-z0-9._-]{3,100}$/]'];
        if (!$this->validate($rules)) return redirect()->to(site_url('dashboard'))->with('error','Please check your profile fields.');
        $email=trim((string)$this->request->getPost('email'));
        $username=trim((string)$this->request->getPost('username'));
        $identityConflict=$db->table('REGISTRATION')->where('email',$email)->where('customer_id !=',$id)->countAllResults();
        $identityConflict += $db->table('EMPLOYEE')->where('email',$email)->countAllResults();
        $usernameConflict=$db->table('REGISTRATION')->where('username',$username)->where('customer_id !=',$id)->countAllResults();
        $usernameConflict += $db->table('EMPLOYEE')->where('username',$username)->countAllResults();
        if ($identityConflict || $usernameConflict) return redirect()->to(site_url('dashboard'))->with('error','That username or email is already in use.');
        $old=(string)$this->request->getPost('current_password'); $new=(string)$this->request->getPost('new_password'); $confirm=(string)$this->request->getPost('confirm_new_password');
        if ($new!=='') {
            if (!password_verify($old,$current['password']) || strlen($new)<12 || !preg_match('/[A-Za-z]/',$new) || !preg_match('/[0-9]/',$new) || $new!==$confirm) return redirect()->to(site_url('dashboard'))->with('error','Password change needs the current password and matching new passwords of at least 12 characters with a letter and a number.');
        }
        $profileData=$this->request->getPost(array_keys($rules));
        $profileData['promotional_opt_in']=$this->request->getPost('promotional_opt_in')==='1'?1:0;
        if ($new!=='') $profileData['password']=password_hash($new,PASSWORD_DEFAULT);
        $db->table('REGISTRATION')->where('customer_id',$id)->update($profileData);
        $this->audit('customer_profile_updated','customer',$id);
        if ($new!=='') $this->audit('customer_password_changed','customer',$id);
        session()->set('username', $username);
        return redirect()->to(site_url('dashboard'))->with('message','Profile saved.');
    }

    public function testimonial()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        $quote=trim((string)$this->request->getPost('quote'));
        if ($quote==='' || mb_strlen($quote)>2000 || $this->request->getPost('consent_to_publish')!=='1') {
            return redirect()->to(site_url('dashboard'))->with('error','Write a short story and agree before it can be reviewed for publication.');
        }
        db_connect()->table('CUSTOMER_TESTIMONIAL')->insert([
            'customer_id'=>(int)session('user_id'),
            'quote'=>$quote,
            'consent_to_publish'=>1,
            'status'=>'pending',
        ]);
        $this->audit('testimonial_submitted','customer',session('user_id'));
        return redirect()->to(site_url('dashboard'))->with('message','Thank you. Your story is awaiting administrator review.');
    }

    public function support()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        $subject=trim((string)$this->request->getPost('subject')); $message=trim((string)$this->request->getPost('message'));
        if ($subject==='' || strlen($subject)>160 || $message==='' || strlen($message)>5000) return redirect()->to(site_url('dashboard'))->with('error','Add a subject and message for support.');
        db_connect()->table('SUPPORT_TICKET')->insert(['customer_id'=>(int)session('user_id'),'ticket_type'=>'support','subject'=>$subject,'message'=>$message]);
        $this->audit('support_ticket_created','customer',session('user_id'),['subject'=>$subject]);
        return redirect()->to(site_url('dashboard'))->with('message','Support request sent.');
    }

    public function quote()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('dashboard/password'));
        $productId=(int)$this->request->getPost('product_id'); $details=trim((string)$this->request->getPost('details'));
        if (strlen($details)>4000) return redirect()->to(site_url('dashboard'))->with('error','Keep quote details under 4,000 characters.');
        $db=db_connect(); $product=$db->table('PRODUCT')->where('product_id',$productId)->where('is_active',1)->get()->getRowArray();
        if (!$product) return redirect()->to(site_url('dashboard'))->with('error','That product is unavailable.');
        $db->table('SUPPORT_TICKET')->insert(['customer_id'=>(int)session('user_id'),'ticket_type'=>'quote','subject'=>'Product quote: '.$product['product_name'],'message'=>$details!==''?$details:'Please contact me with a quote for this product.']);
        $this->audit('product_quote_requested','product',$productId);
        return redirect()->to(site_url('dashboard'))->with('message','Your free quote request was sent to the team.');
    }

    public function staff()
    {
        if (!$this->signedIn()) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('staff/password'));
        $db=db_connect(); $role=(string)session('role');
        if ($role==='hr') return redirect()->to(site_url('staff/users'));
        if ($role==='employee') {
            $announcements=$db->table('ANNOUNCEMENT')->groupStart()->where('audience','employees')->orWhere('audience','all')->groupEnd()->where('publish_at <=',date('Y-m-d H:i:s'))->orderBy('publish_at','DESC')->limit(30)->get()->getResultArray();
            return view('portal/announcements',compact('announcements'));
        }
        if ($role==='technician') {
            $jobs=$db->table('SERVICE_BOOKING b')->select('b.booking_id,b.scheduled_at,b.status,s.service_name,c.first_name,c.last_name,c.address')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->join('REGISTRATION c','c.customer_id=b.customer_id')->where('b.assigned_technician_id',(int)session('user_id'))->orderBy('b.scheduled_at','ASC')->get()->getResultArray();
            $announcements=$db->table('ANNOUNCEMENT')->groupStart()->where('audience','employees')->orWhere('audience','all')->groupEnd()->where('publish_at <=',date('Y-m-d H:i:s'))->orderBy('publish_at','DESC')->limit(10)->get()->getResultArray();
            $reports=$db->table('SERVICE_REPORT')->where('employee_id',(int)session('user_id'))->orderBy('created_at','DESC')->limit(10)->get()->getResultArray();
            return view('portal/technician',compact('jobs','announcements','reports'));
        }
        if (!in_array($role,['admin','hr','dispatcher','employee'],true)) return redirect()->to(site_url('login'));
        $bookings=$db->table('SERVICE_BOOKING b')->select('b.*,s.service_name,c.first_name,c.last_name,c.phone_number,c.address,e.first_name AS tech_first,e.last_name AS tech_last')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->join('REGISTRATION c','c.customer_id=b.customer_id')->join('EMPLOYEE e','e.employee_id=b.assigned_technician_id','left')->orderBy('b.scheduled_at','DESC')->get()->getResultArray();
        $technicians=$db->table('EMPLOYEE')->where('role','technician')->where('is_active',1)->get()->getResultArray();
        $tickets=$db->table('SUPPORT_TICKET t')->select('t.*,c.first_name,c.last_name,e.first_name AS assignee_first,e.last_name AS assignee_last')->join('REGISTRATION c','c.customer_id=t.customer_id')->join('EMPLOYEE e','e.employee_id=t.assigned_employee_id','left')->orderBy('t.created_at','DESC')->get()->getResultArray();
        $reports=$db->table('SERVICE_REPORT r')->select('r.*,e.first_name,e.last_name,b.booking_id')->join('EMPLOYEE e','e.employee_id=r.employee_id')->join('SERVICE_BOOKING b','b.booking_id=r.booking_id')->orderBy('r.created_at','DESC')->get()->getResultArray();
        $supportAgents=$db->table('EMPLOYEE')->whereIn('role',['admin','dispatcher'])->where('is_active',1)->orderBy('first_name')->get()->getResultArray();
        $counts=['customers'=>$db->table('REGISTRATION')->countAllResults(),'bookings'=>$db->table('SERVICE_BOOKING')->countAllResults(),'employees'=>$db->table('EMPLOYEE')->where('is_active',1)->countAllResults(),'pending'=>$db->table('SERVICE_BOOKING')->where('status','requested')->countAllResults(),'confirmed'=>$db->table('SERVICE_BOOKING')->whereIn('status',['approved','scheduled'])->countAllResults(),'completed'=>$db->table('SERVICE_BOOKING')->where('status','completed')->countAllResults(),'cancelled'=>$db->table('SERVICE_BOOKING')->where('status','cancelled')->countAllResults(),'revenue'=>$db->table('PAYMENT')->selectSum('amount')->where('status','paid')->get()->getRow()->amount ?? 0,'attendance'=>$db->table('TECHNICIAN_CHECKIN')->where('checked_in_at >=',date('Y-m-d 00:00:00'))->countAllResults()];
        $services=$db->table('SERVICE_CATEGORY')->orderBy('service_name')->get()->getResultArray();
        $products=$db->table('PRODUCT')->orderBy('product_name')->get()->getResultArray();
        $availabilityRows=$db->table('SERVICE_AVAILABILITY')->get()->getResultArray();
        $availabilityByService=[];
        foreach($availabilityRows as $slot) $availabilityByService[(int)$slot['service_id']][(int)$slot['weekday']]=$slot;
        $employees=$db->table('EMPLOYEE')->orderBy('last_name')->get()->getResultArray();
        $announcements=$db->table('ANNOUNCEMENT')->orderBy('publish_at','DESC')->limit(10)->get()->getResultArray();
        $serviceUsage=$db->table('SERVICE_BOOKING b')->select('s.service_name,COUNT(*) AS total')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->groupBy('s.service_id')->orderBy('total','DESC')->get()->getResultArray();
        $monthInput=(string)$this->request->getGet('month');
        $calendarMonth=\DateTimeImmutable::createFromFormat('!Y-m-d',preg_match('/^\d{4}-\d{2}$/',$monthInput)?$monthInput.'-01':date('Y-m-01')) ?: new \DateTimeImmutable('first day of this month');
        $monthStart=$calendarMonth->modify('first day of this month');
        $gridStart=$monthStart->modify('-'.($monthStart->format('N')-1).' days');
        $monthEnd=$monthStart->modify('last day of this month');
        $gridEnd=$monthEnd->modify('+'.(7-(int)$monthEnd->format('N')).' days');
        $bookingsByDay=[];
        foreach($bookings as $booking) $bookingsByDay[substr($booking['scheduled_at'],0,10)][]=$booking;
        $calendarDays=[];
        for($day=$gridStart;$day<=$gridEnd;$day=$day->modify('+1 day')) {
            $key=$day->format('Y-m-d');
            $calendarDays[]=['date'=>$day,'inMonth'=>$day->format('Y-m')===$monthStart->format('Y-m'),'bookings'=>$bookingsByDay[$key]??[]];
        }
        $checkins=$role==='admin'?$db->table('TECHNICIAN_CHECKIN c')->select('c.*,e.first_name,e.last_name,b.booking_id')->join('EMPLOYEE e','e.employee_id=c.employee_id')->join('SERVICE_BOOKING b','b.booking_id=c.booking_id','left')->orderBy('c.checked_in_at','DESC')->limit(20)->get()->getResultArray():[];
        $recentActivity=$role==='admin'?$db->table('ACTIVITY_LOG a')->select('a.*,e.first_name,e.last_name')->join('EMPLOYEE e','e.employee_id=a.employee_id','left')->orderBy('a.created_at','DESC')->limit(20)->get()->getResultArray():[];
        $testimonials=$role==='admin'?$db->table('CUSTOMER_TESTIMONIAL t')->select('t.*,r.first_name,r.last_name')->join('REGISTRATION r','r.customer_id=t.customer_id')->orderBy('t.created_at','DESC')->limit(20)->get()->getResultArray():[];
        $siteSettings=$role==='admin'?array_column($db->table('SYSTEM_SETTING')->get()->getResultArray(),'setting_value','setting_key'):[];
        return view('portal/staff',compact('bookings','technicians','tickets','supportAgents','reports','counts','services','products','employees','announcements','role','serviceUsage','availabilityByService','calendarMonth','calendarDays','checkins','recentActivity','testimonials','siteSettings'));
    }

    public function employeePasswordForm()
    {
        if (!$this->signedIn()) return redirect()->to(site_url('login'));
        return view('portal/password');
    }

    public function employeeProfile()
    {
        if (!$this->signedIn()) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('staff/password'));
        $employee=db_connect()->table('EMPLOYEE')->select('employee_id,first_name,middle_name,last_name,birthdate,gender,email,phone_number,address,username')->where('employee_id',(int)session('user_id'))->where('is_active',1)->get()->getRowArray();
        if (!$employee) return redirect()->to(site_url('logout'));
        return view('portal/employee_profile',compact('employee'));
    }

    public function updateEmployeeProfile()
    {
        if (!$this->signedIn()) return redirect()->to(site_url('login'));
        if (session('must_change_password')) return redirect()->to(site_url('staff/password'));
        $id=(int)session('user_id'); $db=db_connect();
        $rules=['email'=>'required|valid_email|max_length[255]','phone_number'=>'required|max_length[20]','address'=>'required|max_length[255]'];
        if (!$this->validate($rules)) return redirect()->to(site_url('staff/profile'))->with('error','Check your email, phone number, and address.');
        $data=$this->request->getPost(array_keys($rules));
        if ($db->table('EMPLOYEE')->where('employee_id !=',$id)->where('email',$data['email'])->countAllResults() || $db->table('REGISTRATION')->where('email',$data['email'])->countAllResults()) return redirect()->to(site_url('staff/profile'))->with('error','That email is already in use.');
        $db->table('EMPLOYEE')->where('employee_id',$id)->update($data);
        $this->audit('employee_profile_updated','employee',$id);
        return redirect()->to(site_url('staff/profile'))->with('message','Profile updated.');
    }

    public function changeEmployeePassword()
    {
        if (!$this->signedIn()) return redirect()->to(site_url('login'));
        $id=(int)session('user_id'); $db=db_connect();
        $employee=$db->table('EMPLOYEE')->where('employee_id',$id)->where('is_active',1)->get()->getRowArray();
        $current=(string)$this->request->getPost('current_password');
        $new=(string)$this->request->getPost('new_password');
        $confirm=(string)$this->request->getPost('confirm_password');
        if (!$employee || !password_verify($current,$employee['password'])) return redirect()->to(site_url('staff/password'))->with('error','Enter your current password correctly.');
        if (strlen($new)<12 || !preg_match('/[A-Za-z]/',$new) || !preg_match('/[0-9]/',$new) || $new!==$confirm) return redirect()->to(site_url('staff/password'))->with('error','Use a matching password of at least 12 characters with a letter and a number.');
        $db->table('EMPLOYEE')->where('employee_id',$id)->update(['password'=>password_hash($new,PASSWORD_DEFAULT),'must_change_password'=>0]);
        $this->audit('employee_password_changed','employee',$id);
        session()->set('must_change_password',0);
        return redirect()->to(site_url('staff'))->with('message','Password updated. Welcome to your employee portal.');
    }

    public function users()
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $db=db_connect(); $search=trim((string)$this->request->getGet('q')); $roleFilter=(string)$this->request->getGet('role'); $statusFilter=(string)$this->request->getGet('status');
        $employeeQuery=$db->table('EMPLOYEE e')->select('e.employee_id,e.first_name,e.middle_name,e.last_name,e.birthdate,e.gender,e.email,e.phone_number,e.address,e.username,e.department_id,e.role,e.is_active,e.must_change_password,e.created_at,d.department_name')->join('DEPARTMENT d','d.department_id=e.department_id');
        if ($search!=='') $employeeQuery->groupStart()->like('e.first_name',$search)->orLike('e.last_name',$search)->orLike('e.email',$search)->orLike('e.username',$search)->groupEnd();
        if (in_array($roleFilter,['admin','hr','dispatcher','technician','employee'],true)) $employeeQuery->where('e.role',$roleFilter);
        if (in_array($statusFilter,['active','inactive'],true)) $employeeQuery->where('e.is_active',$statusFilter==='active'?1:0);
        $employees=$employeeQuery->orderBy('e.last_name')->get()->getResultArray();
        $customerQuery=$db->table('REGISTRATION')->select('customer_id,first_name,middle_name,last_name,birthdate,gender,email,phone_number,address,username,is_active,created_at');
        if ($search!=='') $customerQuery->groupStart()->like('first_name',$search)->orLike('last_name',$search)->orLike('email',$search)->orLike('username',$search)->groupEnd();
        if (in_array($statusFilter,['active','inactive'],true)) $customerQuery->where('is_active',$statusFilter==='active'?1:0);
        $customers=$customerQuery->orderBy('last_name')->get()->getResultArray();
        $departments=$db->table('DEPARTMENT')->orderBy('department_name')->get()->getResultArray();
        $announcements=$db->table('ANNOUNCEMENT')->whereIn('audience',['employees','all'])->where('publish_at <=',date('Y-m-d H:i:s'))->orderBy('publish_at','DESC')->limit(20)->get()->getResultArray();
        return view('portal/users',compact('employees','customers','departments','announcements','search','roleFilter','statusFilter'));
    }

    public function resetEmployeePassword(int $id)
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        if ($id===(int)session('user_id')) return redirect()->to(site_url('staff/users'))->with('form_errors',['Use your account password-change page to change your own password.']);
        $db=db_connect(); $target=$db->table('EMPLOYEE')->where('employee_id',$id)->get()->getRowArray();
        if (!$target || (session('role')==='hr' && in_array($target['role'],['admin','hr'],true))) return redirect()->to(site_url('staff/users'));
        return redirect()->to(site_url('staff/users'))->with('reset_credentials',$this->issueTemporaryPassword('EMPLOYEE','employee_id',$id,'employee',$target));
    }

    public function resetCustomerPassword(int $id)
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $db=db_connect(); $target=$db->table('REGISTRATION')->where('customer_id',$id)->get()->getRowArray();
        if (!$target) return redirect()->to(site_url('staff/users'));
        return redirect()->to(site_url('staff/users'))->with('reset_credentials',$this->issueTemporaryPassword('REGISTRATION','customer_id',$id,'customer',$target));
    }

    private function issueTemporaryPassword(string $table, string $key, int $id, string $entity, array $target): array
    {
        $temporary=bin2hex(random_bytes(8)).'A7';
        db_connect()->table($table)->where($key,$id)->update(['password'=>password_hash($temporary,PASSWORD_DEFAULT),'must_change_password'=>1]);
        $this->audit($entity.'_password_reset',$entity,$id);
        return ['name'=>$target['first_name'].' '.$target['last_name'],'username'=>$target['username'],'password'=>$temporary];
    }

    public function setEmployeeStatus(int $id)
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $db=db_connect(); $target=$db->table('EMPLOYEE')->where('employee_id',$id)->get()->getRowArray();
        if (!$target || $id===(int)session('user_id') || (session('role')==='hr' && in_array($target['role'],['admin','hr'],true))) return redirect()->to(site_url('staff/users'));
        $active=$this->request->getPost('is_active')==='1'?1:0;
        $db->table('EMPLOYEE')->where('employee_id',$id)->update(['is_active'=>$active]);
        $this->audit($active?'employee_activated':'employee_deactivated','employee',$id);
        return redirect()->to(site_url('staff/users'))->with('message','Employee account status updated.');
    }

    public function setCustomerStatus(int $id)
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $active=$this->request->getPost('is_active')==='1'?1:0;
        db_connect()->table('REGISTRATION')->where('customer_id',$id)->update(['is_active'=>$active]);
        $this->audit($active?'customer_activated':'customer_deactivated','customer',$id);
        return redirect()->to(site_url('staff/users'))->with('message','Customer account status updated.');
    }

    public function reportCsv()
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $rows=db_connect()->table('SERVICE_BOOKING b')
            ->select('b.booking_id,b.scheduled_at,b.status,c.customer_id,c.first_name,c.last_name,s.service_name,p.amount,p.status AS payment_status,p.payment_method,p.reference_number,p.paid_at')
            ->join('REGISTRATION c','c.customer_id=b.customer_id')
            ->join('SERVICE_CATEGORY s','s.service_id=b.service_id')
            ->join('PAYMENT p','p.booking_id=b.booking_id','left')
            ->orderBy('b.scheduled_at','DESC')
            ->get()->getResultArray();
        $stream=fopen('php://temp','r+');
        fputcsv($stream,['Booking ID','Customer ID','Customer','Service','Scheduled at','Booking status','Amount','Payment status','Payment method','Receipt/reference','Paid at']);
        foreach($rows as $row) fputcsv($stream,[$row['booking_id'],$row['customer_id'],$row['first_name'].' '.$row['last_name'],$row['service_name'],$row['scheduled_at'],$row['status'],$row['amount'],$row['payment_status'],$row['payment_method'],$row['reference_number'],$row['paid_at']]);
        rewind($stream); $csv=stream_get_contents($stream); fclose($stream);
        $filename='sun-son-solar-activity-revenue-'.date('Y-m-d').'.csv';
        return $this->response->setHeader('Content-Type','text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition','attachment; filename="'.$filename.'"')
            ->setHeader('Cache-Control','no-store')
            ->setBody($csv);
    }

    public function moderateTestimonial(int $id)
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $status=(string)$this->request->getPost('status');
        if (!in_array($status,['approved','rejected'],true)) return redirect()->to(site_url('staff'))->with('error','Choose approve or reject.');
        $db=db_connect();
        $testimonial=$db->table('CUSTOMER_TESTIMONIAL')->where('testimonial_id',$id)->get()->getRowArray();
        if (!$testimonial || ($status==='approved' && !(int)$testimonial['consent_to_publish'])) return redirect()->to(site_url('staff'))->with('error','This story cannot be published without customer consent.');
        $db->table('CUSTOMER_TESTIMONIAL')->where('testimonial_id',$id)->update(['status'=>$status]);
        $this->audit('testimonial_'.$status,'testimonial',$id);
        return redirect()->to(site_url('staff'))->with('message','Customer story review saved.');
    }

    public function saveSettings()
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $settings=[
            'company_phone'=>trim((string)$this->request->getPost('company_phone')),
            'company_email'=>trim((string)$this->request->getPost('company_email')),
            'facebook_url'=>trim((string)$this->request->getPost('facebook_url')),
            'instagram_url'=>trim((string)$this->request->getPost('instagram_url')),
            'linkedin_url'=>trim((string)$this->request->getPost('linkedin_url')),
            'privacy_notice'=>trim((string)$this->request->getPost('privacy_notice')),
        ];
        if ($settings['company_phone']==='' || strlen($settings['company_phone'])>30 || !preg_match('/^[0-9+(). -]+$/',$settings['company_phone'])) return redirect()->to(site_url('staff'))->with('error','Enter a valid company phone number.');
        if (!filter_var($settings['company_email'],FILTER_VALIDATE_EMAIL) || strlen($settings['company_email'])>255) return redirect()->to(site_url('staff'))->with('error','Enter a valid company email address.');
        foreach(['facebook_url','instagram_url','linkedin_url'] as $key) {
            if ($settings[$key]!=='' && (!filter_var($settings[$key],FILTER_VALIDATE_URL) || !preg_match('#^https?://#i',$settings[$key]))) return redirect()->to(site_url('staff'))->with('error','Social links must be complete HTTP or HTTPS URLs.');
        }
        if (strlen($settings['privacy_notice'])>5000) return redirect()->to(site_url('staff'))->with('error','Keep the privacy notice under 5,000 characters.');
        $db=db_connect(); $db->transStart();
        foreach($settings as $key=>$value) $db->table('SYSTEM_SETTING')->replace(['setting_key'=>$key,'setting_value'=>$value]);
        $db->transComplete();
        $this->audit('company_settings_updated','system_settings');
        if (!$db->transStatus()) return redirect()->to(site_url('staff'))->with('error','Settings could not be saved.');
        return redirect()->to(site_url('staff'))->with('message','Company contact and privacy settings saved.');
    }

    public function deactivate(int $id)
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $target=db_connect()->table('EMPLOYEE')->where('employee_id',$id)->get()->getRowArray();
        if (!$target || (session('role')==='hr' && in_array($target['role'],['admin','hr'],true))) return redirect()->to(site_url('staff/users'));
        if ($id !== (int)session('user_id')) db_connect()->table('EMPLOYEE')->where('employee_id',$id)->update(['is_active'=>0]);
        if ($id !== (int)session('user_id')) $this->audit('employee_deactivated','employee',$id);
        return redirect()->to(site_url('staff/users'))->with('message','Employee account deactivated.');
    }

    public function updateEmployee(int $id)
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $rules=['first_name'=>'required|max_length[50]','middle_name'=>'permit_empty|max_length[50]','last_name'=>'required|max_length[50]','birthdate'=>'required|valid_date[Y-m-d]','gender'=>'required|in_list[Female,Male,Other]','email'=>'required|valid_email|max_length[255]','phone_number'=>'required|max_length[20]','address'=>'required|max_length[255]','username'=>'required|regex_match[/^[A-Za-z0-9._-]{3,100}$/]','department_id'=>'required|is_natural_no_zero|is_not_unique[DEPARTMENT.department_id]','role'=>'required|in_list[admin,hr,dispatcher,technician,employee]'];
        if (!$this->validate($rules)) return redirect()->to(site_url('staff/users'))->with('form_errors',$this->validator->getErrors());
        $data=$this->request->getPost(array_keys($rules)); $db=db_connect();
        if (session('role')==='hr' && in_array($data['role'],['admin','hr'],true)) return redirect()->to(site_url('staff/users'))->with('form_errors',['HR accounts cannot grant administrator or HR roles.']);
        $conflict=$db->table('EMPLOYEE')->where('employee_id !=',$id)->groupStart()->where('email',$data['email'])->orWhere('username',$data['username'])->groupEnd()->countAllResults();
        if ($conflict || $db->table('REGISTRATION')->groupStart()->where('email',$data['email'])->orWhere('username',$data['username'])->groupEnd()->countAllResults()) return redirect()->to(site_url('staff/users'))->with('form_errors',['Username or email is already used by another account.']);
        $db->table('EMPLOYEE')->where('employee_id',$id)->update($data);
        $this->audit('employee_updated','employee',$id,['role'=>$data['role'],'department_id'=>$data['department_id']]);
        return redirect()->to(site_url('staff/users'))->with('message','Employee account updated.');
    }

    public function updateBooking(int $id)
    {
        if (!$this->staffRoles(['admin','dispatcher'])) return redirect()->to(site_url('staff'));
        $status=(string)$this->request->getPost('status'); $valid=['requested','approved','declined','scheduled','in_progress','completed','cancelled'];
        if (!in_array($status,$valid,true)) return redirect()->to(site_url('staff'));
        $db=db_connect(); $old=$db->table('SERVICE_BOOKING')->where('booking_id',$id)->get()->getRowArray();
        if (!$old) return redirect()->to(site_url('staff'));
        $assign=$this->request->getPost('assigned_technician_id'); $technician=$assign!==''?(int)$assign:null;
        if ($technician && !$db->table('EMPLOYEE')->where('employee_id',$technician)->where('role','technician')->where('is_active',1)->countAllResults()) return redirect()->to(site_url('staff'))->with('error','Choose an active technician account.');
        $scheduled=trim((string)$this->request->getPost('scheduled_at'));
        if ($scheduled==='') $scheduled=$old['reschedule_requested_at'] ?: $old['scheduled_at'];
        $time=strtotime($scheduled);
        if (!$time) return redirect()->to(site_url('staff'))->with('error','Choose a valid schedule.');
        $scheduleChanged=date('Y-m-d H:i:s',$time)!==$old['scheduled_at'];
        if ($scheduleChanged && $time<time()) return redirect()->to(site_url('staff'))->with('error','Choose a future appointment time.');
        $starts=date('Y-m-d H:i:s',$time); $data=['status'=>$status,'assigned_technician_id'=>$technician,'scheduled_at'=>$starts];
        if ($scheduleChanged && !in_array($status,['cancelled','declined'],true) && !$this->isServiceAvailableAt((int)$old['service_id'],$time)) return redirect()->to(site_url('staff'))->with('error','That appointment falls outside this service’s configured availability.');
        $service=$db->table('SERVICE_CATEGORY')->where('service_id',$old['service_id'])->get()->getRowArray();
        $duration=max(1,(int)($service['duration_minutes'] ?? 60)); $ends=$time+($duration*60);
        if ($technician && !in_array($status,['cancelled','declined','completed'],true)) {
            $appointments=$db->table('SERVICE_BOOKING')->where('assigned_technician_id',$technician)->where('booking_id !=',$id)->whereNotIn('status',['cancelled','declined','completed'])->get()->getResultArray();
            foreach($appointments as $appointment) {
                $otherService=$db->table('SERVICE_CATEGORY')->select('duration_minutes')->where('service_id',$appointment['service_id'])->get()->getRowArray();
                $otherStart=strtotime($appointment['scheduled_at']); $otherEnd=$otherStart+max(1,(int)($otherService['duration_minutes']??60))*60;
                if ($time < $otherEnd && $ends > $otherStart) return redirect()->to(site_url('staff'))->with('error','That technician already has an appointment during this time.');
            }
        }
        $db->table('SERVICE_BOOKING')->where('booking_id',$id)->update($data+['reschedule_requested_at'=>null]);
        $booking=$db->table('SERVICE_BOOKING')->where('booking_id',$id)->get()->getRowArray();
        if ($booking) $db->table('CUSTOMER_NOTIFICATION')->insert(['customer_id'=>$booking['customer_id'],'title'=>'Service schedule update','message'=>'Your booking is now ' . str_replace('_',' ',$status) . ' for ' . $starts . '.','notification_type'=>'schedule']);
        $this->audit('booking_updated','booking',$id,['status'=>$status,'technician_id'=>$technician,'scheduled_at'=>$starts]);
        return redirect()->to(site_url('staff'))->with('message','Booking updated and customer notified.');
    }

    public function technicianUpdateBooking(int $id)
    {
        if (!$this->staffRoles(['technician'])) return redirect()->to(site_url('staff'));
        $status=(string)$this->request->getPost('status');
        if (!in_array($status,['in_progress','completed'],true)) return redirect()->to(site_url('staff'));
        $db=db_connect(); $booking=$db->table('SERVICE_BOOKING')->where('booking_id',$id)->where('assigned_technician_id',(int)session('user_id'))->whereIn('status',['scheduled','in_progress'])->get()->getRowArray();
        if (!$booking) return redirect()->to(site_url('staff'))->with('error','This job is not assigned to your account or cannot be updated.');
        $db->table('SERVICE_BOOKING')->where('booking_id',$id)->update(['status'=>$status]);
        $db->table('CUSTOMER_NOTIFICATION')->insert(['customer_id'=>$booking['customer_id'],'title'=>'Service progress update','message'=>'Your service is now ' . str_replace('_',' ',$status) . '.','notification_type'=>'schedule']);
        $this->audit('technician_job_status_updated','booking',$id,['status'=>$status]);
        return redirect()->to(site_url('staff'))->with('message','Job status updated.');
    }

    public function announcement()
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $title=trim((string)$this->request->getPost('title')); $message=trim((string)$this->request->getPost('message')); $audience=(string)$this->request->getPost('audience'); $type=(string)$this->request->getPost('announcement_type');
        if (session('role')==='hr') $audience='employees';
        if ($title==='' || $message==='' || strlen($title)>160 || !in_array($audience,['customers','employees','all'],true) || !in_array($type,['update','promotion','system','tip'],true)) return redirect()->to(site_url('staff'))->with('error','Complete the announcement fields.');
        $db=db_connect(); $db->table('ANNOUNCEMENT')->insert(['created_by'=>(int)session('user_id'),'audience'=>$audience,'announcement_type'=>$type,'title'=>$title,'message'=>$message]);
        $this->audit('announcement_published','announcement',$db->insertID(),['audience'=>$audience,'type'=>$type]);
        return redirect()->to(site_url('staff'))->with('message','Announcement published.');
    }

    public function updateService(int $id)
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $price=$this->request->getPost('base_price');
        $db=db_connect();
        $days=$this->request->getPost('available_days') ?? [];
        if (!is_array($days)) $days=[];
        if ($price!=='' && (filter_var($price,FILTER_VALIDATE_FLOAT)===false || (float)$price<0)) return redirect()->to(site_url('staff'))->with('error','Enter a non-negative service price.');
        $slots=[];
        foreach ([0,1,2,3,4,5,6] as $day) {
            if (in_array((string)$day,$days,true)) {
                $start=(string)$this->request->getPost('start_time_'.$day); $end=(string)$this->request->getPost('end_time_'.$day);
                if (!preg_match('/^\d{2}:\d{2}$/',$start) || !preg_match('/^\d{2}:\d{2}$/',$end) || $start >= $end) return redirect()->to(site_url('staff'))->with('error','Set a valid start and end time for each selected weekday.');
                $slots[]=['service_id'=>$id,'weekday'=>$day,'starts_at'=>$start.':00','ends_at'=>$end.':00','is_available'=>1];
            }
        }
        $db->transStart();
        $db->table('SERVICE_CATEGORY')->where('service_id',$id)->update(['base_price'=>$price!==''?(float)$price:null,'is_active'=>$this->request->getPost('is_active')==='1'?1:0]);
        $db->table('SERVICE_AVAILABILITY')->where('service_id',$id)->delete();
        if ($slots) $db->table('SERVICE_AVAILABILITY')->insertBatch($slots);
        $db->transComplete();
        $this->audit('service_settings_updated','service',$id,['base_price'=>$price]);
        return redirect()->to(site_url('staff'))->with('message','Service settings saved.');
    }

    public function createService()
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $name=trim((string)$this->request->getPost('service_name'));
        $description=trim((string)$this->request->getPost('description'));
        if ($name==='' || strlen($name)>100) return redirect()->to(site_url('staff'))->with('error','Service name is required (100 characters maximum).');
        $db=db_connect();
        if ($db->table('SERVICE_CATEGORY')->where('service_name',$name)->countAllResults()) return redirect()->to(site_url('staff'))->with('error','That service already exists.');
        $db->table('SERVICE_CATEGORY')->insert(['service_name'=>$name,'description'=>$description,'base_price'=>null,'is_active'=>1]);
        $this->audit('service_created','service',$db->insertID());
        return redirect()->to(site_url('staff'))->with('message','Service category added. Set its price and availability below.');
    }

    public function createProduct()
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $name=trim((string)$this->request->getPost('product_name')); $description=trim((string)$this->request->getPost('description')); $image=trim((string)$this->request->getPost('image_url')); $price=$this->request->getPost('price');
        if ($name==='' || strlen($name)>120 || strlen($description)>5000 || strlen($image)>500 || ($image!=='' && (!filter_var($image,FILTER_VALIDATE_URL) || !preg_match('#^https?://#i',$image))) || ($price!=='' && (filter_var($price,FILTER_VALIDATE_FLOAT)===false || (float)$price<0))) return redirect()->to(site_url('staff'))->with('error','Check the product name, description, image URL, and non-negative price.');
        $db=db_connect();
        if ($db->table('PRODUCT')->where('product_name',$name)->countAllResults()) return redirect()->to(site_url('staff'))->with('error','That product already exists.');
        $db->table('PRODUCT')->insert(['product_name'=>$name,'description'=>$description,'image_url'=>$image?:null,'price'=>$price!==''?(float)$price:null,'is_active'=>1]);
        $this->audit('product_created','product',$db->insertID());
        return redirect()->to(site_url('staff'))->with('message','Product listing added.');
    }

    public function updateProduct(int $id)
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $name=trim((string)$this->request->getPost('product_name')); $description=trim((string)$this->request->getPost('description')); $image=trim((string)$this->request->getPost('image_url')); $price=$this->request->getPost('price');
        if ($name==='' || strlen($name)>120 || strlen($description)>5000 || strlen($image)>500 || ($image!=='' && (!filter_var($image,FILTER_VALIDATE_URL) || !preg_match('#^https?://#i',$image))) || ($price!=='' && (filter_var($price,FILTER_VALIDATE_FLOAT)===false || (float)$price<0))) return redirect()->to(site_url('staff'))->with('error','Check the product name, description, image URL, and non-negative price.');
        $db=db_connect(); $duplicate=$db->table('PRODUCT')->where('product_name',$name)->where('product_id !=',$id)->countAllResults();
        if ($duplicate) return redirect()->to(site_url('staff'))->with('error','That product name is already in use.');
        $db->table('PRODUCT')->where('product_id',$id)->update(['product_name'=>$name,'description'=>$description,'image_url'=>$image?:null,'price'=>$price!==''?(float)$price:null,'is_active'=>$this->request->getPost('is_active')==='1'?1:0]);
        $this->audit('product_updated','product',$id);
        return redirect()->to(site_url('staff'))->with('message','Product listing updated.');
    }

    public function recordPayment()
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $booking=(int)$this->request->getPost('booking_id'); $amount=filter_var($this->request->getPost('amount'),FILTER_VALIDATE_FLOAT);
        if (!$booking || $amount===false || $amount<0) return redirect()->to(site_url('staff'))->with('error','Enter a booking and valid amount.');
        $db=db_connect();
        if (!$db->table('SERVICE_BOOKING')->where('booking_id',$booking)->countAllResults()) return redirect()->to(site_url('staff'));
        $db->table('PAYMENT')->insert(['booking_id'=>$booking,'amount'=>$amount,'payment_method'=>substr(trim((string)$this->request->getPost('payment_method')),0,50),'reference_number'=>substr(trim((string)$this->request->getPost('reference_number')),0,100),'status'=>'paid','paid_at'=>date('Y-m-d H:i:s')]);
        $this->audit('payment_recorded','booking',$booking,['amount'=>$amount]);
        return redirect()->to(site_url('staff'))->with('message','Payment and receipt reference recorded.');
    }

    public function checkin()
    {
        if (!$this->staffRoles(['technician'])) return redirect()->to(site_url('staff'));
        $lat=filter_var($this->request->getPost('latitude'),FILTER_VALIDATE_FLOAT); $lng=filter_var($this->request->getPost('longitude'),FILTER_VALIDATE_FLOAT); $job=(int)$this->request->getPost('booking_id');
        if ($lat===false || $lng===false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) return redirect()->to(site_url('staff'))->with('error','Location could not be verified. Allow location access and try again.');
        $db=db_connect();
        if ($job && !$db->table('SERVICE_BOOKING')->where('booking_id',$job)->where('assigned_technician_id',(int)session('user_id'))->countAllResults()) return redirect()->to(site_url('staff'));
        $db->table('TECHNICIAN_CHECKIN')->insert(['employee_id'=>(int)session('user_id'),'booking_id'=>$job ?: null,'latitude'=>$lat,'longitude'=>$lng]);
        $this->audit('technician_checkin','booking',$job ?: null,['latitude'=>$lat,'longitude'=>$lng]);
        return redirect()->to(site_url('staff'))->with('message','Site check-in recorded.');
    }

    public function report()
    {
        if (!$this->staffRoles(['technician'])) return redirect()->to(site_url('staff'));
        $job=(int)$this->request->getPost('booking_id'); $type=(string)$this->request->getPost('report_type'); $details=trim((string)$this->request->getPost('details'));
        $db=db_connect(); $assigned=$db->table('SERVICE_BOOKING')->where('booking_id',$job)->where('assigned_technician_id',(int)session('user_id'))->countAllResults();
        if (!$assigned || !in_array($type,['issue','equipment','customer_concern','other'],true) || $details==='') return redirect()->to(site_url('staff'))->with('error','Select an assigned job and enter report details.');
        $db->table('SERVICE_REPORT')->insert(['booking_id'=>$job,'employee_id'=>(int)session('user_id'),'report_type'=>$type,'details'=>$details]);
        $this->audit('service_report_submitted','booking',$job,['report_type'=>$type]);
        return redirect()->to(site_url('staff'))->with('message','Service report sent to the admin dashboard.');
    }

    public function updateTicket(int $id)
    {
        if (!$this->staffRoles(['admin','dispatcher'])) return redirect()->to(site_url('staff'));
        $status=(string)$this->request->getPost('status'); $reply=trim((string)$this->request->getPost('response')); $assign=$this->request->getPost('assigned_employee_id');
        if (!in_array($status,['open','in_progress','resolved','closed'],true) || strlen($reply)>5000) return redirect()->to(site_url('staff'))->with('error','Check the support response and status.');
        $db=db_connect(); $ticket=$db->table('SUPPORT_TICKET')->where('ticket_id',$id)->get()->getRowArray(); if (!$ticket) return redirect()->to(site_url('staff'));
        $data=['status'=>$status];
        if ($reply!=='') {$data['response']=$reply; $data['responded_at']=date('Y-m-d H:i:s');}
        if ($assign!==null) {
            $assignee=(int)$assign;
            if ($assignee && !$db->table('EMPLOYEE')->where('employee_id',$assignee)->whereIn('role',['admin','dispatcher'])->where('is_active',1)->countAllResults()) return redirect()->to(site_url('staff'))->with('error','Choose an active support coordinator.');
            $data['assigned_employee_id']=$assignee ?: null;
        }
        $db->table('SUPPORT_TICKET')->where('ticket_id',$id)->update($data);
        if ($reply!=='') $db->table('CUSTOMER_NOTIFICATION')->insert(['customer_id'=>$ticket['customer_id'],'title'=>'Support replied','message'=>'A team member responded to “'.$ticket['subject'].'”. Sign in to view the reply.','notification_type'=>'system']);
        $this->audit('support_ticket_updated','support_ticket',$id,['status'=>$status,'assigned_employee_id'=>$data['assigned_employee_id']??null,'responded'=>(bool)$reply]);
        return redirect()->to(site_url('staff'))->with('message','Support ticket saved.');
    }
}
