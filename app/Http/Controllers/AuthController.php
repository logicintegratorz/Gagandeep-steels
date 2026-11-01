<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLoginEvent;
use App\Support\DashboardContacts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\RateLimiter;


use Illuminate\Validation\ValidationException;


final class AuthController
{
    public function loginForm() { return view('auth.login'); }
    public function login(Request $request)
    {
        if (is_string($request->email)) $request->merge(['email' => trim($request->email)]);
        $data = $request->validate(['email' => ['required','string','max:255'], 'password' => ['required','string','max:255']]);
        $identifier=$data['email'];$normalizedEmail=strtolower($identifier);$normalizedPhone=preg_replace('/\D+/','',$identifier);
        $account=User::where(fn($q)=>$q->whereRaw('LOWER(email) = ?',[$normalizedEmail])->when($normalizedPhone,fn($q)=>$q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'(',''),')','') = ?",[$normalizedPhone])))->first();
        $key = 'login:'.hash('sha256', $identifier.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) throw ValidationException::withMessages(['email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.']);
        if (!$account || !Auth::attempt(['id'=>$account->id,'password'=>$data['password'],'active'=>true], false)) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages(['email' => 'The email or password is incorrect, or the account is inactive.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put([
            'last_activity_at' => now()->timestamp,
            'login_expires_at' => now('Asia/Kolkata')->startOfDay()->addDay()->timestamp,
            'session_version' => $request->user()->session_version,
        ]);
        $loginAt = now('Asia/Kolkata');
        $request->user()->forceFill(['last_login_at' => $loginAt])->save();
        UserLoginEvent::create(['user_id' => $request->user()->id, 'logged_in_at' => $loginAt]);
        return redirect()->route('home')->with('success', 'Signed in successfully. Welcome back.');
    }
    public function logout(Request $request)
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login')->with('status', 'You have been signed out successfully.');
    }
    public function session(Request $request)
    {
        return response()->json([
            'serverNow' => now()->timestamp,
            'idleExpiresAt' => (int) $request->session()->get('last_activity_at') + 1800,
            'midnightExpiresAt' => (int) $request->session()->get('login_expires_at'),
        ])->header('Cache-Control', 'no-store');
    }
    public function home(Request $request)
    {
        $dashboardGroups=[];$recentLeads=null;$untouchedCount=0;$touchedCount=0;
        if($request->user()->allows('leads.view')) {
            $leads=\App\Models\Lead::visibleTo($request->user());
            $work=\App\Models\LeadWorkItem::visibleTo($request->user())->where('status','Pending');
            $dashboardGroups=[
                'Total Leads'=>['Today’s Leads'=>(clone $leads)->whereDate('created_at',today())->count(),'Uncontacted Leads'=>(clone $leads)->where('stage','New')->count(),'Opened Leads'=>(clone $leads)->whereNotIn('stage',['Won','Lost'])->count(),'Converted Leads'=>(clone $leads)->where('stage','Won')->count(),'Site Visits'=>(clone $leads)->where('stage','Site Approval')->count(),'Manual Leads'=>(clone $leads)->whereNull('connection_id')->count()],
                'Total Tasks'=>['Overdue Tasks'=>(clone $work)->where('kind','Task')->where('due_at','<',now())->count(),'My Tasks'=>(clone $work)->where('kind','Task')->where('assigned_to',$request->user()->id)->count(),'My Overdue Tasks'=>(clone $work)->where('kind','Task')->where('due_at','<',now())->where('assigned_to',$request->user()->id)->count()],
                'Opportunities'=>collect(['Initial Contact','Enquiry Confirmed','Negotiation','Site Approval','Paperwork'])->mapWithKeys(fn($stage)=>[$stage=>(clone $leads)->where('stage',$stage)->count()])->all(),
                'Reminders'=>['All Reminders'=>(clone $work)->where('kind','Reminder')->count(),'My Reminders'=>(clone $work)->where('kind','Reminder')->where('assigned_to',$request->user()->id)->count(),'Follow-ups'=>(clone $work)->where('kind','Follow-up')->count(),'Due Today'=>(clone $work)->whereDate('due_at',today())->count()],
            ];
            if (!$request->user()->allows('leads.tasks')) unset($dashboardGroups['Total Tasks']);
            if (!$request->user()->allows('leads.reminders') && !$request->user()->allows('leads.followups')) {
                unset($dashboardGroups['Reminders']);
            } elseif (!$request->user()->allows('leads.reminders')) {
                $dashboardGroups['Reminders'] = ['Follow-ups' => $dashboardGroups['Reminders']['Follow-ups']];
            } elseif (!$request->user()->allows('leads.followups')) {
                unset($dashboardGroups['Reminders']['Follow-ups']);
            }
            $contacts=DashboardContacts::query($request);
            $perPage=(int)$request->input('per_page',25);
            if(!in_array($perPage,[25,50,100],true))$perPage=25;
            $recentLeads=$contacts->latest('id')->paginate($perPage)->withQueryString();
            $untouchedCount=(clone $leads)->untouched()->count();
            $touchedCount=(clone $leads)->where(fn($touched)=>$touched->whereNotNull('owner_id')->orWhereNotNull('contact_saved_at')->orWhereHas('visits'))->count();
        }
        $scoreSettings=$request->user()->allows('leads.view')?\App\Models\LeadSetting::find(1):null;
        $callCounts = null;
        $missedCallsCount = null;
        if ($request->user()->isAdmin()) {
            $calls = \App\Models\LeadActivity::query()->where('kind', 'Call log');
            $callCounts = [
                'Outgoing Calls' => (clone $calls)->where('action', 'like', 'Outgoing call%')->count(),
                'Incoming Calls' => (clone $calls)->where('action', 'like', 'Incoming call%')->count(),
            ];
            $missedCallsCount = (clone $calls)->where('action', 'like', 'Missed call%')->count();
        }
        $calendarUsers=$request->user()->isAdmin()?\App\Models\User::where('active',true)->with('role')->orderBy('name')->get():collect([$request->user()]);
        $multiUsers=$request->user()->allows('leads.view')?\App\Models\User::where('active',true)->whereHas('role')->orderBy('name')->get()->filter(fn($user)=>$user->allows('leads.view')):collect();
        $multiLists=$request->user()->allows('leads.view')?\App\Models\LeadGroup::where('created_by',$request->user()->id)->orderBy('name')->get():collect();
        return view('dashboard',compact('dashboardGroups','recentLeads','untouchedCount','touchedCount','scoreSettings','calendarUsers','callCounts','missedCallsCount','multiUsers','multiLists'));
    }
}

