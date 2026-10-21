<?php

namespace App\Controllers;

class Portal extends BaseController
{
    private function signedIn(string $type = 'employee'): bool
    {
        return session('user_type') === $type && (int) session('user_id') > 0;
    }

    private function staffRoles(array $roles): bool
    {
        return $this->signedIn() && in_array(session('role'), $roles, true);
    }

    public function customer()
    {
        if (! $this->signedIn('customer')) return redirect()->to(site_url('login'));
        $db = db_connect(); $id = (int) session('user_id');
        $profile = $db->table('REGISTRATION')->where('customer_id', $id)->get()->getRowArray();
        $bookings = $db->table('SERVICE_BOOKING b')->select('b.*, s.service_name, e.first_name AS tech_first, e.last_name AS tech_last')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->join('EMPLOYEE e','e.employee_id=b.assigned_technician_id','left')->where('b.customer_id',$id)->orderBy('b.scheduled_at','DESC')->get()->getResultArray();
        $services = $db->table('SERVICE_CATEGORY')->where('is_active',1)->orderBy('service_name')->get()->getResultArray();
        $notifications = $db->table('CUSTOMER_NOTIFICATION')->where('customer_id',$id)->orderBy('created_at','DESC')->limit(10)->get()->getResultArray();
        $announcements = $db->table('ANNOUNCEMENT')->groupStart()->where('audience','customers')->orWhere('audience','all')->groupEnd()->where('publish_at <=',date('Y-m-d H:i:s'))->groupStart()->where('expires_at IS NULL',null,false)->orWhere('expires_at >=',date('Y-m-d H:i:s'))->groupEnd()->orderBy('publish_at','DESC')->limit(6)->get()->getResultArray();
        $tickets = $db->table('SUPPORT_TICKET')->where('customer_id',$id)->orderBy('created_at','DESC')->get()->getResultArray();
        $history = $db->table('SERVICE_BOOKING b')->select('b.booking_id,b.scheduled_at,b.status,s.service_name,p.amount,p.status AS payment_status,p.reference_number')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->join('PAYMENT p','p.booking_id=b.booking_id','left')->where('b.customer_id',$id)->whereIn('b.status',['completed','cancelled'])->orderBy('b.scheduled_at','DESC')->get()->getResultArray();
        return view('portal/customer', compact('profile','bookings','services','notifications','announcements','tickets','history'));
    }

    public function book()
    {
        if (! $this->signedIn('customer')) return redirect()->to(site_url('login'));
        $rules=['service_id'=>'required|is_natural_no_zero','scheduled_at'=>'required','customer_notes'=>'permit_empty|max_length[2000]'];
        if (! $this->validate($rules)) return redirect()->to(site_url('dashboard'))->with('error','Choose a service and appointment time.');
        $date = strtotime((string)$this->request->getPost('scheduled_at'));
        if (!$date || $date < time()) return redirect()->to(site_url('dashboard'))->with('error','Choose a future appointment time.');
        $db=db_connect(); $service=(int)$this->request->getPost('service_id');
        if (!$db->table('SERVICE_CATEGORY')->where('service_id',$service)->where('is_active',1)->countAllResults()) return redirect()->to(site_url('dashboard'))->with('error','That service is unavailable.');
        $slots=$db->table('SERVICE_AVAILABILITY')->where('service_id',$service)->where('is_available',1)->get()->getResultArray();
        if ($slots) {
            $weekday=(int)date('w',$date); $clock=date('H:i:s',$date); $allowed=false;
            foreach($slots as $slot) if ((int)$slot['weekday']===$weekday && $clock >= $slot['starts_at'] && $clock < $slot['ends_at']) $allowed=true;
            if (!$allowed) return redirect()->to(site_url('dashboard'))->with('error','That time is outside this service’s available hours.');
        }
        $db->table('SERVICE_BOOKING')->insert(['customer_id'=>(int)session('user_id'),'service_id'=>$service,'scheduled_at'=>date('Y-m-d H:i:s',$date),'customer_notes'=>trim((string)$this->request->getPost('customer_notes'))]);
        return redirect()->to(site_url('dashboard'))->with('message','Your service request was submitted.');
    }

    public function cancel(int $id)
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        db_connect()->table('SERVICE_BOOKING')->where('booking_id',$id)->where('customer_id',(int)session('user_id'))->whereIn('status',['requested','approved','scheduled'])->update(['status'=>'cancelled']);
        return redirect()->to(site_url('dashboard'))->with('message','Booking updated.');
    }

    public function profile()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        $id=(int)session('user_id'); $db=db_connect();
        $current=$db->table('REGISTRATION')->where('customer_id',$id)->get()->getRowArray();
        $rules=['first_name'=>'required|max_length[50]','middle_name'=>'permit_empty|max_length[50]','last_name'=>'required|max_length[50]','birthdate'=>'required|valid_date[Y-m-d]','gender'=>'required|in_list[Female,Male,Other]','email'=>'required|valid_email|max_length[255]','phone_number'=>'required|max_length[20]','address'=>'required|max_length[255]'];
        if (!$this->validate($rules)) return redirect()->to(site_url('dashboard'))->with('error','Please check your profile fields.');
        $email=trim((string)$this->request->getPost('email'));
        $identityConflict=$db->table('REGISTRATION')->where('email',$email)->where('customer_id !=',$id)->countAllResults();
        $identityConflict += $db->table('EMPLOYEE')->where('email',$email)->countAllResults();
        if ($identityConflict) return redirect()->to(site_url('dashboard'))->with('error','That email is already in use.');
        $old=(string)$this->request->getPost('current_password'); $new=(string)$this->request->getPost('new_password');
        if ($new!=='') {
            if (!password_verify($old,$current['password']) || strlen($new)<12 || !preg_match('/[A-Za-z]/',$new) || !preg_match('/[0-9]/',$new)) return redirect()->to(site_url('dashboard'))->with('error','Password change needs the current password and a new password of at least 12 characters with a letter and a number.');
        }
        $profileData=$this->request->getPost(array_keys($rules));
        if ($new!=='') $profileData['password']=password_hash($new,PASSWORD_DEFAULT);
        $db->table('REGISTRATION')->where('customer_id',$id)->update($profileData);
        return redirect()->to(site_url('dashboard'))->with('message','Profile saved.');
    }

    public function support()
    {
        if (!$this->signedIn('customer')) return redirect()->to(site_url('login'));
        $subject=trim((string)$this->request->getPost('subject')); $message=trim((string)$this->request->getPost('message'));
        if ($subject==='' || strlen($subject)>160 || $message==='' || strlen($message)>5000) return redirect()->to(site_url('dashboard'))->with('error','Add a subject and message for support.');
        db_connect()->table('SUPPORT_TICKET')->insert(['customer_id'=>(int)session('user_id'),'subject'=>$subject,'message'=>$message]);
        return redirect()->to(site_url('dashboard'))->with('message','Support request sent.');
    }

    public function staff()
    {
        if (!$this->signedIn()) return redirect()->to(site_url('login'));
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
        $tickets=$db->table('SUPPORT_TICKET t')->select('t.*,c.first_name,c.last_name')->join('REGISTRATION c','c.customer_id=t.customer_id')->orderBy('t.created_at','DESC')->get()->getResultArray();
        $reports=$db->table('SERVICE_REPORT r')->select('r.*,e.first_name,e.last_name,b.booking_id')->join('EMPLOYEE e','e.employee_id=r.employee_id')->join('SERVICE_BOOKING b','b.booking_id=r.booking_id')->orderBy('r.created_at','DESC')->get()->getResultArray();
        $counts=['customers'=>$db->table('REGISTRATION')->countAllResults(),'bookings'=>count($bookings),'revenue'=>$db->table('PAYMENT')->selectSum('amount')->where('status','paid')->get()->getRow()->amount ?? 0];
        $services=$db->table('SERVICE_CATEGORY')->orderBy('service_name')->get()->getResultArray();
        $availabilityRows=$db->table('SERVICE_AVAILABILITY')->get()->getResultArray();
        $availabilityByService=[];
        foreach($availabilityRows as $slot) $availabilityByService[(int)$slot['service_id']][(int)$slot['weekday']]=$slot;
        $employees=$db->table('EMPLOYEE')->orderBy('last_name')->get()->getResultArray();
        $announcements=$db->table('ANNOUNCEMENT')->orderBy('publish_at','DESC')->limit(10)->get()->getResultArray();
        $serviceUsage=$db->table('SERVICE_BOOKING b')->select('s.service_name,COUNT(*) AS total')->join('SERVICE_CATEGORY s','s.service_id=b.service_id')->groupBy('s.service_id')->orderBy('total','DESC')->get()->getResultArray();
        return view('portal/staff',compact('bookings','technicians','tickets','reports','counts','services','employees','announcements','role','serviceUsage','availabilityByService'));
    }

    public function users()
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $db=db_connect(); $employees=$db->table('EMPLOYEE e')->select('e.*,d.department_name')->join('DEPARTMENT d','d.department_id=e.department_id')->orderBy('e.last_name')->get()->getResultArray();
        $departments=$db->table('DEPARTMENT')->orderBy('department_name')->get()->getResultArray();
        return view('portal/users',compact('employees','departments'));
    }

    public function deactivate(int $id)
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $target=db_connect()->table('EMPLOYEE')->where('employee_id',$id)->get()->getRowArray();
        if (!$target || (session('role')==='hr' && in_array($target['role'],['admin','hr'],true))) return redirect()->to(site_url('staff/users'));
        if ($id !== (int)session('user_id')) db_connect()->table('EMPLOYEE')->where('employee_id',$id)->update(['is_active'=>0]);
        return redirect()->to(site_url('staff/users'))->with('message','Employee account deactivated.');
    }

    public function updateEmployee(int $id)
    {
        if (!$this->staffRoles(['admin','hr'])) return redirect()->to(site_url('staff'));
        $rules=['first_name'=>'required|max_length[50]','middle_name'=>'permit_empty|max_length[50]','last_name'=>'required|max_length[50]','email'=>'required|valid_email|max_length[255]','phone_number'=>'required|max_length[20]','address'=>'required|max_length[255]','department_id'=>'required|is_natural_no_zero','role'=>'required|in_list[admin,hr,dispatcher,technician,employee]'];
        if (!$this->validate($rules)) return redirect()->to(site_url('staff/users'))->with('form_errors',$this->validator->getErrors());
        $data=$this->request->getPost(array_keys($rules)); $db=db_connect();
        if (session('role')==='hr' && in_array($data['role'],['admin','hr'],true)) return redirect()->to(site_url('staff/users'))->with('form_errors',['HR accounts cannot grant administrator or HR roles.']);
        $conflict=$db->table('EMPLOYEE')->where('employee_id !=',$id)->where('email',$data['email'])->countAllResults();
        if ($conflict || $db->table('REGISTRATION')->where('email',$data['email'])->countAllResults()) return redirect()->to(site_url('staff/users'))->with('form_errors',['Email is already used by another account.']);
        $db->table('EMPLOYEE')->where('employee_id',$id)->update($data);
        return redirect()->to(site_url('staff/users'))->with('message','Employee account updated.');
    }

    public function updateBooking(int $id)
    {
        if (!$this->staffRoles(['admin','dispatcher'])) return redirect()->to(site_url('staff'));
        $status=(string)$this->request->getPost('status'); $valid=['requested','approved','declined','scheduled','in_progress','completed','cancelled'];
        if (!in_array($status,$valid,true)) return redirect()->to(site_url('staff'));
        $assign=$this->request->getPost('assigned_technician_id'); $data=['status'=>$status,'assigned_technician_id'=>$assign!==''?(int)$assign:null];
        if ($assign!=='' && !db_connect()->table('EMPLOYEE')->where('employee_id',(int)$assign)->where('role','technician')->where('is_active',1)->countAllResults()) return redirect()->to(site_url('staff'))->with('error','Choose an active technician account.');
        $scheduled=trim((string)$this->request->getPost('scheduled_at'));
        if ($scheduled!=='') { $time=strtotime($scheduled); if (!$time || $time<time()) return redirect()->to(site_url('staff'))->with('error','Choose a valid future schedule.'); $data['scheduled_at']=date('Y-m-d H:i:s',$time); }
        db_connect()->table('SERVICE_BOOKING')->where('booking_id',$id)->update($data);
        $booking=db_connect()->table('SERVICE_BOOKING')->where('booking_id',$id)->get()->getRowArray();
        if ($booking) db_connect()->table('CUSTOMER_NOTIFICATION')->insert(['customer_id'=>$booking['customer_id'],'title'=>'Service schedule update','message'=>'Your booking status is now ' . str_replace('_',' ',$status) . '.','notification_type'=>'schedule']);
        return redirect()->to(site_url('staff'))->with('message','Booking updated and customer notified.');
    }

    public function announcement()
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $title=trim((string)$this->request->getPost('title')); $message=trim((string)$this->request->getPost('message')); $audience=(string)$this->request->getPost('audience');
        if ($title==='' || $message==='' || !in_array($audience,['customers','employees','all'],true)) return redirect()->to(site_url('staff'))->with('error','Complete the announcement fields.');
        $db=db_connect(); $db->table('ANNOUNCEMENT')->insert(['created_by'=>(int)session('user_id'),'audience'=>$audience,'title'=>$title,'message'=>$message]);
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
        return redirect()->to(site_url('staff'))->with('message','Service category added. Set its price and availability below.');
    }

    public function recordPayment()
    {
        if (!$this->staffRoles(['admin'])) return redirect()->to(site_url('staff'));
        $booking=(int)$this->request->getPost('booking_id'); $amount=filter_var($this->request->getPost('amount'),FILTER_VALIDATE_FLOAT);
        if (!$booking || $amount===false || $amount<0) return redirect()->to(site_url('staff'))->with('error','Enter a booking and valid amount.');
        $db=db_connect();
        if (!$db->table('SERVICE_BOOKING')->where('booking_id',$booking)->countAllResults()) return redirect()->to(site_url('staff'));
        $db->table('PAYMENT')->insert(['booking_id'=>$booking,'amount'=>$amount,'payment_method'=>substr(trim((string)$this->request->getPost('payment_method')),0,50),'reference_number'=>substr(trim((string)$this->request->getPost('reference_number')),0,100),'status'=>'paid','paid_at'=>date('Y-m-d H:i:s')]);
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
        return redirect()->to(site_url('staff'))->with('message','Site check-in recorded.');
    }

    public function report()
    {
        if (!$this->staffRoles(['technician'])) return redirect()->to(site_url('staff'));
        $job=(int)$this->request->getPost('booking_id'); $type=(string)$this->request->getPost('report_type'); $details=trim((string)$this->request->getPost('details'));
        $db=db_connect(); $assigned=$db->table('SERVICE_BOOKING')->where('booking_id',$job)->where('assigned_technician_id',(int)session('user_id'))->countAllResults();
        if (!$assigned || !in_array($type,['issue','equipment','customer_concern','other'],true) || $details==='') return redirect()->to(site_url('staff'))->with('error','Select an assigned job and enter report details.');
        $db->table('SERVICE_REPORT')->insert(['booking_id'=>$job,'employee_id'=>(int)session('user_id'),'report_type'=>$type,'details'=>$details]);
        return redirect()->to(site_url('staff'))->with('message','Service report sent to the admin dashboard.');
    }

    public function updateTicket(int $id)
    {
        if (!$this->staffRoles(['admin','dispatcher'])) return redirect()->to(site_url('staff'));
        $status=(string)$this->request->getPost('status');
        if (in_array($status,['open','in_progress','resolved','closed'],true)) db_connect()->table('SUPPORT_TICKET')->where('ticket_id',$id)->update(['status'=>$status]);
        return redirect()->to(site_url('staff'));
    }
}
